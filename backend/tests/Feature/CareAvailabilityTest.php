<?php

namespace Tests\Feature;

use App\Models\CareEntry;
use App\Models\CareUnavailability;
use App\Services\Care\CareAvailability;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    private array $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-10-02T12:00:00Z'));
        $this->scenario = CareScenario::create('child');
        $this->login($this->scenario['owner']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function login($user): void
    {
        $this->actingAs($user, 'api')->withHeader('X-Tenant', $this->scenario['group']->slug);
    }

    private function periods(): string
    {
        return '/api/recipients/'.$this->scenario['recipient']->id.'/my-unavailabilities';
    }

    private function entries(): string
    {
        return '/api/recipients/'.$this->scenario['recipient']->id.'/entries';
    }

    public function test_agenda_includes_visible_unavailability_but_excludes_observers_cancelled_and_outside_periods(): void
    {
        $id = $this->period('2026-10-01', '2026-10-02');
        $cancelled = $this->period();
        $this->postJson($this->periods().'/'.$cancelled.'/cancel')->assertOk();
        $this->period('2026-10-03', '2026-10-03');
        $this->period('2026-10-01', '2026-10-01');
        $observer = CareScenario::member($this->scenario['group'], 'observador');
        $grant = CareScenario::grant($this->scenario['recipient'], $observer, ['routine'], false);
        $period = CareUnavailability::findOrFail($id);
        $period->update(['reason' => 'Viagem']);
        $period->replicate()->fill(['user_id' => $observer->id])->save();
        $url = '/api/agenda?from=2026-10-02&to=2026-10-02';
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'unavailabilities')
            ->assertJsonPath('unavailabilities.0.id', 'unavailability-'.$id)
            ->assertJsonPath('unavailabilities.0.kind', 'unavailability')
            ->assertJsonPath('unavailabilities.0.reason', 'Viagem')
            ->assertJsonPath('unavailabilities.0.can_execute', false);
        $this->getJson($url.'&executor='.$observer->id)->assertOk()->assertJsonCount(0, 'unavailabilities');
        $this->login($observer);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'unavailabilities');
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'unavailabilities');
    }

    private function period(string $start = '2026-10-02', string $end = '2026-10-02'): int
    {
        return $this->postJson($this->periods(), ['starts_at' => $start, 'ends_at' => $end, 'timezone' => 'America/Sao_Paulo', 'user_id' => 999])
            ->assertCreated()->json('id');
    }

    public function test_dates_include_entire_last_day_and_respect_timezone_and_dst(): void
    {
        $response = $this->postJson($this->periods(), [
            'starts_at' => '2026-03-08', 'ends_at' => '2026-03-08', 'timezone' => 'America/New_York',
        ])->assertCreated()->assertJsonPath('starts_at', '2026-03-08')->assertJsonPath('ends_at', '2026-03-08');
        $period = CareUnavailability::findOrFail($response->json('id'));
        $this->assertSame('2026-03-08T05:00:00+00:00', $period->intervalStart()->toIso8601String());
        $this->assertSame('2026-03-09T04:00:00+00:00', $period->intervalEnd()->toIso8601String());
        $availability = app(CareAvailability::class);
        $recipient = $this->scenario['recipient'];
        $userId = $this->scenario['owner']->id;
        $this->assertNull($availability->conflict($recipient, $userId, $period->intervalStart()->subSecond()));
        $this->assertNotNull($availability->conflict($recipient, $userId, $period->intervalStart()));
        $this->assertNotNull($availability->conflict($recipient, $userId, $period->intervalEnd()->subSecond()));
        $this->assertNull($availability->conflict($recipient, $userId, $period->intervalEnd()));
        $this->assertNotNull($availability->conflict($recipient, $userId, $period->intervalStart()->subMinute(), $period->intervalStart()->addMinute()));
        $this->getJson('/api/agenda?from=2026-03-08&to=2026-03-08')->assertOk()
            ->assertJsonPath('unavailabilities.0.starts_at', '2026-03-08')
            ->assertJsonPath('unavailabilities.0.ends_at', '2026-03-08');
        $this->postJson($this->periods(), ['starts_at' => '2026-02-30', 'ends_at' => '2026-03-01', 'timezone' => 'America/Sao_Paulo'])
            ->assertUnprocessable()->assertJsonValidationErrors('starts_at');
    }

    public function test_own_period_converts_timezone_validates_and_cancels_without_deleting_history(): void
    {
        $id = $this->period();
        $this->getJson($this->periods())->assertOk()->assertJsonPath('data.0.user_id', $this->scenario['owner']->id)
            ->assertJsonPath('data.0.starts_at', '2026-10-02');
        $this->postJson($this->periods(), ['starts_at' => '2026-10-02', 'ends_at' => '2026-10-01', 'timezone' => 'America/Sao_Paulo'])->assertUnprocessable();
        $this->postJson($this->periods(), ['starts_at' => '2026-03-08T02:30', 'ends_at' => '2026-03-08T04:00', 'timezone' => 'America/New_York'])->assertUnprocessable();
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertOk();
        $this->assertNotNull(CareUnavailability::findOrFail($id)->cancelled_at);
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertConflict();
    }

    public function test_only_self_can_cancel_and_observer_or_revoked_access_cannot_manage(): void
    {
        $id = $this->period();
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        $grant = CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $this->login($caregiver);
        $this->getJson($this->periods())->assertJsonCount(0, 'data');
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertNotFound();
        $this->period();
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson($this->periods())->assertForbidden();
        $observer = CareScenario::member($this->scenario['group'], 'observador');
        CareScenario::grant($this->scenario['recipient'], $observer, ['routine'], false);
        $this->login($observer);
        $this->postJson($this->periods(), ['starts_at' => '2026-10-02', 'ends_at' => '2026-10-02', 'timezone' => 'America/Sao_Paulo'])->assertForbidden();
    }

    public function test_assignment_is_blocked_for_partial_overlap_and_explicit_responsibility_but_not_boundary(): void
    {
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $this->login($caregiver);
        $this->period();
        $this->login($this->scenario['owner']);
        $before = CareEntry::count();
        $data = ['kind' => 'task', 'title' => 'Cuidado', 'assigned_user_id' => $caregiver->id, 'due_at' => '2026-10-02T10:30:00Z', 'ends_at' => '2026-10-02T11:30:00Z'];
        $this->postJson($this->entries(), $data)->assertUnprocessable()->assertJsonValidationErrors('assigned_user_id');
        $this->assertSame($before, CareEntry::count());
        $this->postJson($this->entries(), [...$data, 'assigned_user_id' => null, 'affected_user_ids' => [$caregiver->id]])->assertUnprocessable();
        $this->postJson($this->entries(), [...$data, 'due_at' => '2026-10-03T03:00:00Z', 'ends_at' => null])->assertCreated();
        $this->postJson($this->entries(), [...$data, 'due_at' => '2026-10-02T02:00:00Z', 'ends_at' => '2026-10-02T03:00:00Z'])->assertCreated();
    }

    public function test_weekly_series_checks_later_occurrences_and_does_not_block_non_matching_days(): void
    {
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $this->login($caregiver);
        $this->period('2026-10-09', '2026-10-09');
        $this->login($this->scenario['owner']);
        $rule = ['frequency' => 'weekly', 'timezone' => 'America/Sao_Paulo', 'local_start' => '2026-10-02T09:00', 'until' => '2026-10-16', 'duration_minutes' => 30, 'weekdays' => [5]];
        $data = ['kind' => 'feeding', 'title' => 'Alimentação', 'assigned_user_id' => $caregiver->id, 'schedule' => $rule];
        $this->postJson($this->entries(), $data)->assertUnprocessable();
        $this->postJson($this->entries(), [...$data, 'schedule' => [...$rule, 'weekdays' => [6]]])->assertCreated();
    }

    public function test_new_period_blocks_pending_assignment_acceptance_but_allows_rejection(): void
    {
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $entry = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Passeio', 'assigned_user_id' => $caregiver->id, 'due_at' => '2026-10-02T12:00:00Z'])->assertCreated()->json();
        $this->login($caregiver);
        $this->period();
        $url = $this->entries().'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision';
        $this->postJson($url, ['decision' => 'accepted'])->assertConflict();
        $this->assertSame(0, CareEntry::findOrFail($entry['id'])->revision);
        $this->postJson($url, ['decision' => 'rejected', 'reason' => 'Estarei ausente.'])->assertOk();
    }

    public function test_approved_undated_care_cannot_execute_during_period_even_with_backdated_time(): void
    {
        $entry = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Cuidado avulso'])->assertCreated()->json();
        $id = $this->period();
        $this->getJson($this->entries())->assertJsonPath('0.can_execute', false);
        $url = $this->entries().'/'.$entry['id'].'/execution';
        $this->postJson($url, ['occurred_at' => '2026-10-01T12:00:00Z'])->assertUnprocessable();
        $this->assertFalse(CareEntry::where('related_entry_id', $entry['id'])->exists());
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertOk();
        $this->postJson($url, ['occurred_at' => now()->toISOString()])->assertCreated();
    }

    public function test_occurrence_and_actual_execution_time_in_period_remain_blocked_after_period_ends(): void
    {
        $entry = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Passeio', 'due_at' => '2026-10-02T12:00:00Z', 'publish_to_agenda' => true])->assertCreated()->json();
        $this->period();
        $this->travelTo(CarbonImmutable::parse('2026-10-03T04:00:00Z'));
        $row = $this->getJson('/api/agenda?from=2026-10-02&to=2026-10-02')->assertOk()->assertJsonPath('data.0.can_execute', false)->json('data.0');
        $this->assertStringContainsString('Execução impedida', $row['execution_block']);
        $this->postJson('/api/occurrences/'.$row['id'].'/execution', ['occurred_at' => now()->toISOString()])->assertUnprocessable();
        $this->assertFalse(CareEntry::where('related_entry_id', $entry['id'])->exists());
        $avulso = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Avulso'])->assertCreated()->json('id');
        $this->postJson($this->entries().'/'.$avulso.'/execution', ['occurred_at' => '2026-10-02T12:00:00Z'])->assertUnprocessable();
        $this->postJson($this->entries().'/'.$avulso.'/execution', ['occurred_at' => '2026-10-03T03:00:00Z'])->assertCreated();
    }

    public function test_period_is_isolated_to_recipient_and_group(): void
    {
        $this->period();
        $owner = $this->scenario['owner'];
        $other = CareScenario::create('pet');
        CareScenario::member($other['group'], 'responsavel', $owner);
        CareScenario::grant($other['recipient'], $owner, ['routine']);
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $other['group']->slug);
        $url = '/api/recipients/'.$other['recipient']->id.'/my-unavailabilities';
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($this->periods())->assertNotFound();
        app(CurrentOrganization::class)->set($other['group']);
        $this->assertNull(app(CareAvailability::class)->conflict($other['recipient'], $owner->id, CarbonImmutable::now()));
    }

    public function test_unavailability_does_not_suspend_consultation_or_require_automatic_deciders_to_be_available(): void
    {
        $responsible = CareScenario::member($this->scenario['group'], 'responsavel');
        CareScenario::grant($this->scenario['recipient'], $responsible, ['routine']);
        $this->login($responsible);
        $this->period();
        $this->getJson('/api/recipients/'.$this->scenario['recipient']->id)->assertOk();
        $this->login($this->scenario['owner']);
        $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Responsabilidade própria', 'assigned_user_id' => $this->scenario['owner']->id, 'due_at' => now()->toISOString()])->assertCreated();
    }

    public function test_revision_cannot_introduce_conflicting_assignment_and_preserves_previous_record(): void
    {
        $entry = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Cuidado original', 'due_at' => now()->toISOString()])->assertCreated()->json();
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $this->login($caregiver);
        $this->period();
        $this->login($this->scenario['owner']);
        $this->putJson($this->entries().'/'.$entry['id'], ['kind' => 'task', 'title' => 'Nova atribuição', 'assigned_user_id' => $caregiver->id])->assertUnprocessable();
        $original = CareEntry::findOrFail($entry['id']);
        $this->assertSame('Cuidado original', $original->title);
        $this->assertSame(1, $original->proposals()->count());
    }

    public function test_daily_series_and_health_types_are_blocked_and_cancel_restores_assignment(): void
    {
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine', 'health']);
        $this->login($caregiver);
        $id = $this->period('2026-10-04', '2026-10-04');
        $this->login($this->scenario['owner']);
        foreach (['medication', 'vaccine', 'event'] as $kind) {
            $this->postJson($this->entries(), ['kind' => $kind, 'title' => 'Cuidado', 'assigned_user_id' => $caregiver->id, 'due_at' => '2026-10-04T12:00:00Z'])->assertUnprocessable();
        }
        $data = ['kind' => 'task', 'title' => 'Diário', 'assigned_user_id' => $caregiver->id, 'schedule' => ['frequency' => 'daily', 'timezone' => 'America/Sao_Paulo', 'local_start' => '2026-10-02T09:00', 'until' => '2026-10-05', 'duration_minutes' => 0]];
        $this->postJson($this->entries(), $data)->assertUnprocessable();
        $this->login($caregiver);
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertOk();
        $this->login($this->scenario['owner']);
        $this->postJson($this->entries(), $data)->assertCreated();
    }

    public function test_self_assignment_at_start_is_blocked_and_period_list_is_paginated(): void
    {
        $this->period();
        $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Próprio', 'assigned_user_id' => $this->scenario['owner']->id, 'due_at' => '2026-10-02T11:00:00Z'])->assertUnprocessable();
        $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Fora', 'assigned_user_id' => $this->scenario['owner']->id, 'due_at' => '2026-10-03T03:00:00Z'])->assertCreated();
        for ($i = 0; $i < 20; $i++) {
            $this->period();
        }
        $this->getJson($this->periods())->assertJsonCount(20, 'data')->assertJsonPath('last_page', 2);
        $this->getJson($this->periods().'?page=2')->assertJsonCount(1, 'data');
    }

    public function test_approved_assignment_conflict_is_visible_without_transferring_executor(): void
    {
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $entry = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Passeio', 'assigned_user_id' => $caregiver->id, 'due_at' => now()->toISOString(), 'publish_to_agenda' => true])->assertCreated()->json();
        $this->login($caregiver);
        $this->postJson($this->entries().'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $this->period();
        $this->login($this->scenario['owner']);
        $row = $this->getJson('/api/agenda?from=2026-10-02&to=2026-10-02')->assertOk()->json('data.0');
        $this->assertSame($caregiver->id, $row['assigned_user_id']);
        $this->assertFalse($row['can_execute']);
        $this->assertStringContainsString('Não é possível compartilhar o cuidado', $row['availability_conflict']);
        $this->assertSame($caregiver->id, CareEntry::findOrFail($entry['id'])->assigned_user_id);
    }

    public function test_empty_agenda_identifies_availability_recipient_only_for_authorized_editor(): void
    {
        $url = '/api/agenda?from=2026-10-02&to=2026-10-02';
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data')
            ->assertJsonPath('availability_recipient.id', $this->scenario['recipient']->id)
            ->assertJsonPath('availability_recipient.name', $this->scenario['recipient']->name);
        $observer = CareScenario::member($this->scenario['group'], 'observador');
        CareScenario::grant($this->scenario['recipient'], $observer, ['routine'], false);
        $this->login($observer);
        $this->getJson($url)->assertOk()->assertJsonPath('availability_recipient.id', $this->scenario['recipient']->id)->assertJsonPath('availability_recipient.can_manage', false);
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        $grant = CareScenario::grant($this->scenario['recipient'], $caregiver, ['health']);
        $this->login($caregiver);
        $this->getJson($url)->assertOk()->assertJsonPath('availability_recipient.id', $this->scenario['recipient']->id);
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson($url)->assertOk()->assertJsonPath('availability_recipient', null);
    }

    public function test_optional_reason_is_persisted_and_shared_with_authorized_readers(): void
    {
        $data = ['starts_at' => '2026-10-02', 'ends_at' => '2026-10-02', 'timezone' => 'America/Sao_Paulo'];
        $this->postJson($this->periods(), [...$data, 'reason' => '  Motivo pessoal reservado  '])->assertCreated()->assertJsonPath('reason', 'Motivo pessoal reservado');
        $this->getJson($this->periods())->assertOk()->assertJsonPath('data.0.reason', 'Motivo pessoal reservado');
        $this->postJson($this->periods(), $data)->assertCreated()->assertJsonPath('reason', null);
        $this->postJson($this->periods(), [...$data, 'reason' => '   '])->assertCreated()->assertJsonPath('reason', null);
        $this->postJson($this->periods(), [...$data, 'reason' => str_repeat('x', 2001)])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $caregiver = CareScenario::member($this->scenario['group'], 'cuidador');
        CareScenario::grant($this->scenario['recipient'], $caregiver, ['routine']);
        $this->login($caregiver);
        $this->getJson($this->periods())->assertOk()->assertJsonCount(0, 'data');
        $response = $this->postJson($this->entries(), ['kind' => 'task', 'title' => 'Cuidado', 'assigned_user_id' => $this->scenario['owner']->id, 'due_at' => now()->toISOString()])->assertUnprocessable();
        $this->assertStringContainsString('Motivo pessoal reservado', $response->json('message'));
        $this->getJson(str_replace('my-unavailabilities', 'unavailabilities', $this->periods()))->assertOk()->assertJsonPath('data.0.can_cancel', false)->assertJsonPath('data.2.reason', 'Motivo pessoal reservado');
        $this->assertStringContainsString('Não é possível compartilhar', $response->json('message'));
    }

    public function test_shared_periods_are_visible_to_observers_but_cannot_be_modified_and_revocation_blocks_reading(): void
    {
        $data = ['starts_at' => '2026-10-02', 'ends_at' => '2026-10-02', 'timezone' => 'America/Sao_Paulo', 'reason' => 'Compromisso informado'];
        $id = $this->postJson($this->periods(), $data)->assertCreated()->json('id');
        $observer = CareScenario::member($this->scenario['group'], 'observador');
        $grant = CareScenario::grant($this->scenario['recipient'], $observer, ['routine'], false);
        $this->login($observer);
        $shared = str_replace('my-unavailabilities', 'unavailabilities', $this->periods());
        $this->getJson($shared)->assertOk()->assertJsonPath('data.0.reason', 'Compromisso informado')
            ->assertJsonPath('data.0.user.name', $this->scenario['owner']->name)->assertJsonPath('data.0.can_cancel', false);
        $this->postJson($this->periods().'/'.$id.'/cancel')->assertForbidden();
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson($shared)->assertForbidden();
        $this->assertNull(CareUnavailability::findOrFail($id)->cancelled_at);
    }
}
