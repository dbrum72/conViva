<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareTodayTest extends TestCase
{
    use DatabaseTransactions;

    public function test_facts_and_read_receipts_are_personal_and_do_not_change_proposals(): void
    {
        $this->seed();
        $scenario = CareScenario::create('pet');
        $recipient = $scenario['recipient'];
        $group = $scenario['group'];
        $observer = CareScenario::member($group, 'observador');
        $access = CareScenario::grant($recipient, $observer, ['routine'], false);
        $base = '/api/recipients/'.$recipient->id;
        $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $group->slug);
        $this->getJson('/api/today')->assertOk()->assertJsonPath('quick_facts', ['feeding', 'walk', 'observation']);
        $data = [
            'request_id' => (string) Str::uuid(),
            'kind' => 'walk',
            'occurred_at' => now()->subMinute()->toISOString(),
            'description' => 'Passeio no parque',
        ];
        $fact = $this->postJson($base.'/care-facts', $data)->assertCreated()->json('id');
        $this->postJson($base.'/care-facts', $data)->assertOk()->assertJsonPath('id', $fact);
        $this->assertDatabaseMissing('care_entries', ['care_recipient_id' => $recipient->id]);

        $handover = $this->getJson('/api/today')->assertOk()
            ->assertJsonPath('handover.facts.0.author', $scenario['owner']->name)
            ->assertJsonPath('handover.read_at', null)->json('handover');
        $this->putJson($base.'/handover/read', ['version' => $handover['version']])->assertNoContent();
        $this->assertNotNull($this->getJson('/api/today')->assertOk()->json('handover.read_at'));
        $this->postJson($base.'/care-facts', [
            ...$data, 'request_id' => (string) Str::uuid(), 'kind' => 'observation',
        ])->assertCreated();
        $this->putJson($base.'/handover/read', ['version' => $handover['version']])->assertConflict();

        $this->actingAs($observer, 'api');
        $observerVersion = $this->getJson('/api/today')->assertOk()
            ->assertJsonPath('quick_facts', [])->assertJsonPath('handover.read_at', null)->json('handover.version');
        $this->postJson($base.'/care-facts', $data)->assertForbidden();
        $this->putJson($base.'/handover/read', ['version' => $observerVersion])->assertNoContent();
        $access->delete();
        $this->getJson('/api/today')->assertOk()->assertJsonPath('handover', null);
        $this->putJson($base.'/handover/read', ['version' => $observerVersion])->assertForbidden();
    }

    public function test_today_preserves_area_isolation_and_uses_the_existing_execution_flow(): void
    {
        $this->seed();
        $scenario = CareScenario::create('child');
        $group = $scenario['group'];
        $recipient = $scenario['recipient'];
        $base = '/api/recipients/'.$recipient->id.'/entries';
        $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $group->slug);
        $this->postJson($base, [
            'kind' => 'medication', 'title' => 'Medicamento reservado', 'due_at' => now()->addHour()->toISOString(),
        ])->assertCreated();
        $task = $this->postJson($base, ['kind' => 'task', 'title' => 'Organizar materiais'])
            ->assertCreated()->json('id');
        $this->getJson('/api/today')->assertOk()
            ->assertJsonPath('upcoming.0.title', 'Medicamento reservado')
            ->assertJsonPath('handover.tasks.0.can_execute', true);
        $this->postJson($base.'/'.$task.'/execution', ['occurred_at' => now()->subMinute()->toISOString()])->assertCreated();
        $observer = CareScenario::member($group, 'observador');
        CareScenario::grant($recipient, $observer, ['routine'], false);
        $this->actingAs($observer, 'api');
        $this->getJson('/api/today')->assertOk()
            ->assertJsonPath('upcoming', [])
            ->assertJsonPath('handover.tasks', [])
            ->assertJsonPath('handover.executions.0.author', $scenario['owner']->name)
            ->assertJsonMissing(['title' => 'Medicamento reservado']);
    }
}
