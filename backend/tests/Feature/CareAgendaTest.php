<?php

namespace Tests\Feature;

use App\Jobs\GenerateGroupCareOccurrences;
use App\Models\CareOccurrence;
use App\Services\Care\GenerateCareOccurrences;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareAgendaTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28T12:00:00Z'));
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);

        return $s;
    }

    private function data(array $rule = [], array $extra = []): array
    {
        return [...['publish_to_agenda' => true, 'kind' => 'task', 'title' => 'Passeio', 'schedule' => [...[
            'frequency' => 'daily', 'timezone' => 'America/Sao_Paulo', 'local_start' => '2026-10-01T23:30', 'until' => '2026-10-04', 'duration_minutes' => 60, 'weekdays' => [],
        ], ...$rule]], ...$extra];
    }

    private function agenda(string $extra = '')
    {
        return $this->getJson('/api/agenda?from=2026-10-01&to=2026-10-05'.$extra);
    }

    public function test_publication_is_explicit_for_every_kind_and_requires_a_date(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        foreach (['event', 'task', 'feeding', 'medication', 'vaccine', 'journal', 'expense'] as $kind) {
            $data = ['kind' => $kind, 'title' => $kind, 'due_at' => '2026-10-01T13:00:00Z'];
            if ($kind === 'expense') {
                $data['amount_cents'] = 100;
            }
            $entry = $this->postJson($path, $data)->assertCreated()->assertJsonPath('publish_to_agenda', false)->json();
            $this->agenda('&kind='.$kind)->assertJsonCount(0, 'data');
            $this->putJson($path.'/'.$entry['id'], [...$data, 'publish_to_agenda' => true])->assertOk();
            $this->agenda('&kind='.$kind)->assertJsonCount(1, 'data');
            $this->putJson($path.'/'.$entry['id'], [...$data, 'publish_to_agenda' => false])->assertOk();
            $this->agenda('&kind='.$kind)->assertJsonCount(0, 'data');
        }
        $this->postJson($path, ['kind' => 'task', 'title' => 'Sem data', 'publish_to_agenda' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('due_at');
        $this->postJson($path, ['kind' => 'task', 'title' => 'Inválido', 'publish_to_agenda' => 'yes'])
            ->assertUnprocessable()->assertJsonValidationErrors('publish_to_agenda');
    }

    public function test_unpublished_legacy_and_generated_records_do_not_leak_into_calendar_metadata(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $this->postJson($path, $this->data())->assertCreated();
        $this->postJson($path, $this->data([], ['title' => 'Não publicado', 'publish_to_agenda' => false, 'assigned_user_id' => $s['owner']->id]))->assertCreated();
        $s['recipient']->entries()->create(['organization_id' => $s['group']->id, 'created_by' => $s['owner']->id, 'kind' => 'task', 'title' => 'Legado não publicado', 'due_at' => '2026-10-02 02:30:00', 'revision' => 1]);
        $response = $this->agenda('&per_page=1')->assertJsonPath('total', 4)->assertJsonPath('data.0.conflict', false)->assertJsonCount(0, 'executors');
        $this->assertSame('Passeio', $response->json('data.0.title'));
        $observer = CareScenario::member($s['group'], 'observador');
        $grant = CareScenario::grant($s['recipient'], $observer, ['routine'], false);
        $this->actingAs($observer, 'api')->agenda()->assertJsonCount(4, 'data');
        $grant->update(['expires_at' => now()]);
        $this->agenda()->assertJsonCount(0, 'data')->assertJsonCount(0, 'executors');
    }

    public function test_publication_revision_respects_acceptance_and_author_permissions(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $data = $this->data([], ['publish_to_agenda' => false]);
        $entry = $this->postJson($path, $data)->assertCreated()->json();
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['routine']);
        $change = [...$data, 'publish_to_agenda' => true, 'revision' => 1];
        $this->actingAs($peer, 'api')->putJson($path.'/'.$entry['id'], $change)->assertForbidden();
        $proposal = $this->actingAs($s['owner'], 'api')->putJson($path.'/'.$entry['id'], $change)->assertOk()->json('proposals.0.id');
        $this->agenda()->assertJsonCount(0, 'data');
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->agenda()->assertJsonCount(4, 'data');
        $proposal = $this->actingAs($s['owner'], 'api')->putJson($path.'/'.$entry['id'], [...$data, 'revision' => 2])->assertOk()->json('proposals.0.id');
        $this->agenda()->assertJsonCount(4, 'data');
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->agenda()->assertJsonCount(0, 'data');
        $this->agenda('&status=superseded')->assertJsonCount(0, 'data');
    }

    public function test_daily_generation_is_idempotent_and_uses_group_day_across_utc_midnight(): void
    {
        $s = $this->scenario();
        $this->postJson('/api/recipients/'.$s['recipient']->id.'/entries', $this->data())->assertCreated();
        $this->agenda()->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('data.0.local_date', '2026-10-01')->assertJsonPath('data.0.due_at', '2026-10-02T02:30:00.000000Z');
        $this->agenda()->assertOk()->assertJsonCount(4, 'data');
        $this->assertDatabaseCount('care_occurrences', 4);
        $this->agenda('&per_page=2&page=2')->assertJsonCount(2, 'data')->assertJsonPath('total', 4);
        $this->getJson('/api/agenda?from=2026-01-01&to=2026-12-31')->assertUnprocessable();
    }

    public function test_weekdays_and_dst_keep_wall_time_and_skip_nonexistent_times(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $this->postJson($path, $this->data(['frequency' => 'weekly', 'weekdays' => [4, 6]]))->assertCreated();
        $this->agenda()->assertJsonCount(2, 'data');
        $this->postJson($path, $this->data(['timezone' => 'America/New_York', 'local_start' => '2027-03-13T02:30', 'until' => '2027-03-15']))->assertCreated();
        $response = $this->getJson('/api/agenda?from=2027-03-13&to=2027-03-15')->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame('2027-03-13T07:30:00.000000Z', $response->json('data.0.due_at'));
        $this->assertSame('2027-03-15T06:30:00.000000Z', $response->json('data.1.due_at'));
        $this->postJson($path, $this->data(['timezone' => 'America/New_York', 'local_start' => '2027-03-14T02:30', 'until' => '2027-03-15']))->assertUnprocessable();
    }

    public function test_pending_revision_and_rejection_preserve_occurrences_and_future_cancel_needs_acceptance(): void
    {
        $s = $this->scenario();
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['routine']);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data())->assertCreated()->json();
        $this->agenda()->assertJsonCount(0, 'data');
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $first = $this->agenda()->assertJsonCount(4, 'data')->json('data.0.id');
        $change = $this->actingAs($s['owner'], 'api')->putJson($path.'/'.$entry['id'], $this->data(['local_start' => '2026-10-01T18:00'], ['revision' => 1]))->assertOk()->json();
        $this->agenda()->assertJsonPath('data.0.id', $first)->assertJsonPath('data.0.pending_change', true);
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$change['proposals'][0]['id'].'/decision', ['decision' => 'rejected', 'reason' => 'Horário incompatível'])->assertOk();
        $this->agenda()->assertJsonPath('data.0.id', $first);
        $cancel = $this->actingAs($s['owner'], 'api')->postJson('/api/occurrences/'.$first.'/cancellation', ['scope' => 'future'])->assertOk()->json();
        $this->agenda()->assertJsonCount(4, 'data');
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$cancel['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $this->agenda()->assertJsonCount(0, 'data');
        $this->agenda('&status=cancelled')->assertJsonCount(4, 'data');
        $this->assertDatabaseCount('care_occurrences', 4);
    }

    public function test_single_exception_execution_and_administrative_completion_are_distinct(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data([], ['assigned_user_id' => $s['owner']->id]))->assertCreated()->json();
        $rows = $this->agenda()->json('data');
        $this->postJson('/api/occurrences/'.$rows[1]['id'].'/cancellation', ['scope' => 'one'])->assertOk();
        $this->agenda()->assertJsonCount(3, 'data');
        $this->postJson('/api/occurrences/'.$rows[0]['id'].'/execution', ['occurred_at' => '2026-09-28T11:00:00Z', 'description' => 'Antecipado'])->assertCreated();
        $this->postJson('/api/occurrences/'.$rows[0]['id'].'/execution', ['occurred_at' => '2026-09-28T11:00:00Z'])->assertConflict();
        $this->agenda('&status=executed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.execution.description', 'Antecipado');
        $this->patchJson($path.'/'.$entry['id'].'/complete')->assertOk();
        $this->agenda()->assertJsonCount(2, 'data')->assertJsonPath('data.0.administratively_completed', true)->assertJsonPath('data.0.can_execute', true);
        $this->assertDatabaseHas('care_entries', ['related_entry_id' => $entry['id'], 'created_by' => $s['owner']->id]);
    }

    public function test_approved_timezone_revision_preserves_past_and_replaces_future(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data())->assertCreated()->json();
        $past = $this->agenda()->json('data.0.id');
        $this->travelTo(CarbonImmutable::parse('2026-10-02T12:00:00Z'));
        $this->putJson($path.'/'.$entry['id'], $this->data(['timezone' => 'America/Manaus'], ['revision' => 1]))->assertOk();
        $this->agenda()->assertJsonCount(4, 'data')->assertJsonPath('data.0.id', $past)->assertJsonPath('data.1.due_at', '2026-10-03T03:30:00.000000Z');
        $this->agenda('&status=superseded')->assertJsonCount(3, 'data');
    }

    public function test_area_and_tenant_filtering_precedes_pagination_and_conflicts(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data())->assertCreated()->json();
        $this->postJson($path, ['kind' => 'medication', 'title' => 'Restrito', 'publish_to_agenda' => true, 'due_at' => '2026-10-02T02:30:00Z'])->assertCreated();
        $this->agenda()->assertJsonPath('data.0.conflict', true);
        $observer = CareScenario::member($s['group'], 'observador');
        CareScenario::grant($s['recipient'], $observer, ['routine'], false);
        $row = $this->actingAs($observer, 'api')->agenda('&per_page=1')->assertJsonPath('total', 4)->assertJsonPath('data.0.conflict', false)->assertJsonPath('data.0.can_execute', false)->json('data.0');
        $this->postJson('/api/occurrences/'.$row['id'].'/cancellation', ['scope' => 'one'])->assertForbidden();
        $this->postJson('/api/occurrences/'.$row['id'].'/execution', ['occurred_at' => '2026-09-28T11:00:00Z'])->assertForbidden();
        $other = CareScenario::create('pet');
        $this->actingAs($other['owner'], 'api')->withHeader('X-Tenant', $other['group']->slug)->postJson('/api/occurrences/'.$row['id'].'/execution', ['occurred_at' => '2026-09-28T11:00:00Z'])->assertNotFound();
    }

    public function test_validates_recurrence_and_job_restores_context(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $this->postJson($path, $this->data(['frequency' => 'weekly']))->assertUnprocessable();
        $this->postJson($path, ['kind' => 'task', 'title' => 'Sem regra', 'schedule' => []])->assertUnprocessable();
        $this->postJson($path, $this->data(['timezone' => 'invalid']))->assertUnprocessable();
        $this->postJson($path, $this->data(['until' => '2028-10-01']))->assertUnprocessable();
        $this->postJson($path, $this->data([], ['kind' => 'medication']))->assertUnprocessable();
        $this->postJson($path, $this->data())->assertCreated();
        $context = app(CurrentOrganization::class);
        $context->clear();
        (new GenerateGroupCareOccurrences($s['group']->id))->handle($context, app(GenerateCareOccurrences::class));
        $this->assertFalse($context->has());
        $context->set($s['group']);
        $this->assertSame(4, CareOccurrence::count());
    }

    public function test_dst_fallback_generates_once_and_legacy_execution_is_not_lost(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $this->postJson($path, $this->data(['timezone' => 'America/New_York', 'local_start' => '2026-10-31T01:30', 'until' => '2026-11-02']))->assertCreated();
        $this->getJson('/api/agenda?from=2026-10-31&to=2026-11-02')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.1.due_at', '2026-11-01T05:30:00.000000Z')->assertJsonPath('data.2.due_at', '2026-11-02T06:30:00.000000Z');
        $entry = $s['recipient']->entries()->create(['organization_id' => $s['group']->id, 'created_by' => $s['owner']->id, 'assigned_user_id' => $s['owner']->id, 'kind' => 'task', 'title' => 'Legado', 'publish_to_agenda' => true, 'due_at' => '2026-10-01 13:00:00', 'revision' => 1, 'status' => 'pending']);
        $execution = $s['recipient']->entries()->create(['organization_id' => $s['group']->id, 'created_by' => $s['owner']->id, 'related_entry_id' => $entry->id, 'kind' => 'task', 'title' => 'Execução legada', 'status' => 'completed', 'completed_at' => now(), 'revision' => 1]);
        $this->agenda()->assertJsonCount(0, 'data');
        $this->agenda('&status=executed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.execution.author', $s['owner']->name);
        $this->assertDatabaseHas('care_occurrences', ['execution_entry_id' => $execution->id]);
    }

    public function test_cancellation_becomes_blocked_if_occurrence_passes_but_can_be_rejected(): void
    {
        $s = $this->scenario();
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['routine']);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data())->assertCreated()->json();
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $id = $this->agenda()->json('data.0.id');
        $change = $this->actingAs($s['owner'], 'api')->postJson('/api/occurrences/'.$id.'/cancellation', ['scope' => 'one'])->assertOk()->json();
        $proposal = $change['proposals'][0]['id'];
        $this->travelTo(CarbonImmutable::parse('2026-10-02T12:00:00Z'));
        $this->actingAs($peer, 'api')->getJson('/api/decisions/entry/'.$proposal)->assertOk()->assertJsonPath('can_accept', false)->assertJsonPath('can_reject', true);
        $this->postJson($path.'/'.$entry['id'].'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertConflict();
        $this->postJson($path.'/'.$entry['id'].'/proposals/'.$proposal.'/decision', ['decision' => 'rejected', 'reason' => 'A data já passou'])->assertOk();
        $this->agenda()->assertJsonCount(4, 'data');
    }

    public function test_filters_long_event_overlap_and_execution_after_revocation(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $caregiver = CareScenario::member($s['group'], 'cuidador');
        $grant = CareScenario::grant($s['recipient'], $caregiver, ['routine']);
        $entry = $this->postJson($path, $this->data([], ['assigned_user_id' => $caregiver->id]))->assertCreated()->json();
        $this->actingAs($caregiver, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $id = $this->agenda('&executor='.$caregiver->id.'&kind=task')->assertJsonCount(4, 'data')->json('data.0.id');
        $grant->update(['expires_at' => now()]);
        $this->postJson('/api/occurrences/'.$id.'/execution', ['occurred_at' => now()->subMinute()->toISOString()])->assertForbidden();
        $this->actingAs($s['owner'], 'api')->postJson($path, ['kind' => 'event', 'title' => 'Viagem', 'publish_to_agenda' => true, 'due_at' => '2026-09-29T12:00:00Z', 'ends_at' => '2026-10-04T12:00:00Z'])->assertCreated();
        $this->agenda('&kind=event')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Viagem');
    }

    public function test_revision_does_not_duplicate_early_execution_and_rejects_stale_version(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $data = $this->data([], ['assigned_user_id' => $s['owner']->id]);
        $entry = $this->postJson($path, $data)->assertCreated()->json();
        $id = $this->agenda()->json('data.0.id');
        $this->postJson('/api/occurrences/'.$id.'/execution', ['occurred_at' => now()->subMinute()->toISOString()])->assertCreated();
        $this->putJson($path.'/'.$entry['id'], [...$data, 'title' => 'Revisado', 'revision' => 1])->assertOk();
        $this->agenda()->assertJsonCount(3, 'data');
        $this->agenda('&status=executed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->putJson($path.'/'.$entry['id'], [...$data, 'revision' => 1])->assertConflict();
        $this->putJson($path.'/'.$entry['id'], $data)->assertUnprocessable();
    }

    public function test_group_timezone_is_validated_and_does_not_reinterpret_existing_series(): void
    {
        $s = $this->scenario();
        $this->postJson('/api/groups', ['name' => 'Outro fuso', 'timezone' => 'Invalid/Zone'])->assertUnprocessable();
        $this->postJson('/api/groups', ['name' => 'Outro fuso', 'timezone' => 'America/Manaus'])->assertCreated()->assertJsonPath('timezone', 'America/Manaus');
        $this->postJson('/api/recipients/'.$s['recipient']->id.'/entries', $this->data())->assertCreated();
        $before = $this->agenda()->json('data.0.due_at');
        $s['group']->update(['timezone' => 'Asia/Tokyo']);
        $this->agenda()->assertJsonPath('timezone', 'Asia/Tokyo')->assertJsonPath('data.0.due_at', $before)->assertJsonPath('data.0.timezone', 'America/Sao_Paulo')->assertJsonPath('data.0.local_date', '2026-10-02');
    }

    public function test_revision_and_full_cancellation_close_a_series_with_a_future_cutoff(): void
    {
        $s = $this->scenario();
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, $this->data())->assertCreated()->json();
        $id = $this->agenda()->json('data.2.id');
        $this->postJson('/api/occurrences/'.$id.'/cancellation', ['scope' => 'future'])->assertOk();
        $this->agenda()->assertJsonCount(2, 'data');
        $this->putJson($path.'/'.$entry['id'], $this->data([], ['revision' => 2, 'title' => 'Nova programação']))->assertOk();
        $this->agenda()->assertJsonCount(4, 'data');
        $newId = $this->agenda()->json('data.2.id');
        $this->postJson('/api/occurrences/'.$newId.'/cancellation', ['scope' => 'future'])->assertOk();
        $this->deleteJson($path.'/'.$entry['id'])->assertOk();
        $this->agenda()->assertJsonCount(0, 'data');
    }
}
