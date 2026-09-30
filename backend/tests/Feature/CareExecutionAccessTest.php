<?php

namespace Tests\Feature;

use App\Models\CareEntry;
use App\Models\CareOccurrence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareExecutionAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_closed_unassigned_occurrence_can_be_executed_by_responsible_with_identity_and_time(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, ['kind' => 'task', 'title' => 'Passeio', 'due_at' => now()->subHours(2)->toISOString(), 'publish_to_agenda' => true])->assertCreated()->json();
        $closed = $this->patchJson($path.'/'.$entry['id'].'/complete')->assertOk()->json('completed_at');
        $url = '/api/agenda?from='.now()->subDay()->format('Y-m-d').'&to='.now()->format('Y-m-d');
        $row = $this->getJson($url)->assertOk()->assertJsonPath('data.0.can_execute', true)->assertJsonPath('data.0.is_overdue', true)->json('data.0');
        $execution = '/api/occurrences/'.$row['id'].'/execution';
        $this->postJson($execution, ['occurred_at' => now()->addHour()->toISOString()])->assertUnprocessable();
        $at = now()->subHour()->startOfMinute()->toISOString();
        $this->postJson($execution, ['occurred_at' => $at, 'created_by' => 999])->assertCreated();
        $this->postJson($execution, ['occurred_at' => $at])->assertConflict();
        $this->getJson($url.'&status=executed')->assertJsonPath('data.0.is_overdue', false)->assertJsonPath('data.0.can_execute', false)->assertJsonPath('data.0.execution.author', $s['owner']->name);
        $record = CareEntry::where('related_entry_id', $entry['id'])->firstOrFail();
        $this->assertSame($s['owner']->id, $record->created_by);
        $this->assertSame($at, $record->due_at->toISOString());
        $this->assertNotNull($record->created_at);
        $this->assertSame($closed, CareEntry::findOrFail($entry['id'])->completed_at->toISOString());
    }

    public function test_unassigned_requires_responsibility_and_edit_access_and_assignment_is_respected(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $this->postJson('/api/recipients/'.$s['recipient']->id.'/entries', ['kind' => 'task', 'title' => 'Passeio', 'due_at' => now()->subHour()->toISOString(), 'publish_to_agenda' => true])->assertCreated();
        $url = '/api/agenda?from='.now()->subDay()->format('Y-m-d').'&to='.now()->format('Y-m-d');
        $id = $this->getJson($url)->json('data.0.id');
        foreach (['cuidador', 'observador', 'responsavel'] as $role) {
            $member = CareScenario::member($s['group'], $role);
            $grant = CareScenario::grant($s['recipient'], $member, ['routine'], $role === 'cuidador');
            $this->actingAs($member, 'api')->getJson($url)->assertJsonPath('data.0.can_execute', false);
            $this->postJson('/api/occurrences/'.$id.'/execution', ['occurred_at' => now()->toISOString()])->assertForbidden();
        }
        $grant->update(['can_edit' => true]);
        $this->getJson($url)->assertJsonPath('data.0.can_execute', true);
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/occurrences/'.$id.'/execution', ['occurred_at' => now()->toISOString()])->assertForbidden();
        $this->actingAs($s['owner'], 'api');
        $o = CareOccurrence::findOrFail($id);
        $o->schedule->update(['snapshot' => [...$o->schedule->snapshot, 'assigned_user_id' => $member->id]]);
        $this->getJson($url)->assertJsonPath('data.0.can_execute', false);
        $this->postJson('/api/occurrences/'.$id.'/execution', ['occurred_at' => now()->toISOString()])->assertForbidden();
    }

    public function test_undated_closed_care_can_be_executed_once_by_responsible(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, ['kind' => 'task', 'title' => 'Cuidado avulso'])->assertCreated()->json();
        $this->patchJson($path.'/'.$entry['id'].'/complete')->assertOk()->assertJsonPath('can_execute', true);
        $at = now()->subHour()->startOfMinute()->toISOString();
        $this->postJson($path.'/'.$entry['id'].'/execution', ['occurred_at' => $at])->assertCreated()->assertJsonPath('created_by', $s['owner']->id)->assertJsonPath('due_at', $at);
        $this->postJson($path.'/'.$entry['id'].'/execution', ['occurred_at' => $at])->assertConflict();
    }
}
