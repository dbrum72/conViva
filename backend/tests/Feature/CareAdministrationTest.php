<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareAdministrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_multiple_medication_doses_require_approval_and_keep_a_correction_history(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(18, 0));
        $s = CareScenario::create('child');
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['health']);
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $agenda = '/api/agenda?from=2026-10-01&to=2026-10-02';
        $instruction = $this->postJson($path, [
            'kind' => 'medication', 'title' => 'Instrução', 'details' => ['frequency' => 'Duas vezes ao dia'],
        ])->assertCreated()->json();
        $this->assertNull($instruction['schedule']);
        $entry = $this->postJson($path, [
            'kind' => 'medication', 'title' => 'Medicamento', 'schedule' => [
                'frequency' => 'daily', 'timezone' => 'America/Sao_Paulo',
                'local_start' => '2026-10-01T08:00', 'until' => '2026-10-02',
                'duration_minutes' => 0, 'times' => ['08:00', '16:00'],
            ],
        ])->assertCreated()->assertJsonPath('status', 'awaiting_approval')->json();
        $history = $agenda.'&health_entry='.$entry['id'].'&status=all';
        $this->getJson($history)->assertNotFound();
        $this->actingAs($peer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', [
            'decision' => 'accepted',
        ])->assertOk();
        $this->getJson($agenda)->assertJsonCount(0, 'data');
        $rows = $this->getJson($history)->assertJsonCount(4, 'data')->json('data');
        $this->assertSame('2026-10-01T19:00:00.000000Z', $rows[1]['due_at']);
        $execution = '/api/occurrences/'.$rows[0]['id'].'/execution';
        $data = ['occurred_at' => '2026-10-01T11:15:00Z', 'outcome' => 'not_administered', 'description' => 'Observação inicial'];
        $this->postJson($execution, $data)->assertCreated()->assertJsonPath('status', 'not_administered');
        $this->actingAs($s['owner'], 'api')->postJson($execution, $data)->assertConflict();
        $this->postJson('/api/occurrences/'.$rows[1]['id'].'/execution', [...$data, 'occurred_at' => '2026-10-01T19:10:00Z'])
            ->assertCreated();
        $id = $this->getJson($history)->assertJsonPath('data.0.can_correct', false)->json('data.0.execution.id');
        $correction = $execution.'/'.$id.'/corrections';
        $data['outcome'] = 'administered';
        $this->postJson($correction, [...$data, 'reason' => 'Esclarecimento'])->assertForbidden();
        $this->actingAs($peer, 'api')->postJson($correction, $data)->assertJsonValidationErrors('reason');
        $this->postJson($correction, [...$data, 'reason' => 'Esclarecimento'])->assertCreated()->assertJsonPath('status', 'executed');
        $this->postJson($correction, [...$data, 'reason' => 'Repetição'])->assertConflict();
        $this->getJson($history)
            ->assertJsonPath('data.0.execution.outcome', 'not_administered')
            ->assertJsonPath('data.0.execution.author', $peer->name)
            ->assertJsonPath('data.0.corrections.0.outcome', 'administered')
            ->assertJsonPath('data.0.corrections.0.reason', 'Esclarecimento')
            ->assertJsonPath('data.2.execution', null);

        $observer = CareScenario::member($s['group'], 'observador');
        CareScenario::grant($s['recipient'], $observer, ['routine'], false);
        $this->actingAs($observer, 'api')->getJson($history)->assertNotFound();
        $this->postJson($execution, $data)->assertForbidden();
    }

    public function test_vaccine_next_date_is_informational_and_requires_an_application(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(18, 0));
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $data = ['kind' => 'vaccine', 'title' => 'Vacina', 'publish_to_agenda' => true, 'schedule' => [
            'frequency' => 'daily', 'timezone' => 'America/Sao_Paulo', 'local_start' => '2026-10-02T08:00',
            'until' => '2026-10-02', 'duration_minutes' => 0,
        ]];
        $this->postJson($path, $data)->assertJsonValidationErrors('schedule.frequency');
        $data['schedule']['frequency'] = 'once';
        $this->postJson($path, $data)->assertCreated();
        $agenda = '/api/agenda?from=2026-10-02&to=2026-10-31&kind=vaccine';
        $id = $this->getJson($agenda)->assertJsonCount(1, 'data')->json('data.0.id');
        $execution = '/api/occurrences/'.$id.'/execution';
        $record = ['occurred_at' => '2026-10-02T11:30:00Z', 'next_date' => '2026-10-20'];
        $this->postJson($execution, $record)->assertJsonValidationErrors('outcome');
        $this->postJson($execution, [...$record, 'outcome' => 'not_administered'])->assertJsonValidationErrors('next_date');
        $this->postJson($execution, [...$record, 'outcome' => 'administered'])->assertCreated();
        $this->getJson($agenda.'&status=executed')->assertJsonPath('data.0.execution.next_date', '2026-10-20');
        $this->getJson($agenda)->assertJsonCount(0, 'data');
    }
}
