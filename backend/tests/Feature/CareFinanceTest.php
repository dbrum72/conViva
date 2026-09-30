<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareFinanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_finance_separates_confirmed_proposals_and_history_and_refreshes_totals_after_payment(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id.'/entries';
        $entry = $this->postJson($path, ['kind' => 'expense', 'title' => 'Consulta', 'amount_cents' => 10000])->assertCreated()->json();
        $this->getJson('/api/finance')->assertOk()->assertJsonPath('summary.confirmed_cents', 10000)->assertJsonPath('summary.unpaid_cents', 10000)->assertJsonPath('data.0.can_change', true)->assertJsonPath('data.0.shares.0.can_pay', true)->assertJsonMissingPath('data.0.recipient.health_profile');
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['finance']);
        $this->postJson($path, ['kind' => 'expense', 'title' => 'Proposta', 'amount_cents' => 50000, 'shares' => [['user_id' => $peer->id, 'amount_cents' => 50000]]])->assertCreated()->assertJsonPath('status', 'awaiting_approval');
        $cancelled = $this->postJson($path, ['kind' => 'expense', 'title' => 'Cancelada', 'amount_cents' => 3000])->assertCreated()->json();
        $this->deleteJson($path.'/'.$cancelled['id'])->assertOk();
        $result = $this->getJson('/api/finance')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('summary.confirmed_cents', 10000)->assertJsonPath('summary.paid_cents', 0);
        $this->assertEqualsCanonicalizing(['confirmed', 'proposals', 'history'], array_column($result->json('data'), 'finance_group'));
        $this->postJson($path.'/'.$entry['id'].'/shares/'.$entry['shares'][0]['id'].'/pay')->assertOk();
        $result = $this->getJson('/api/finance')->assertOk()->assertJsonPath('summary.paid_cents', 10000)->assertJsonPath('summary.unpaid_cents', 0);
        $paid = collect($result->json('data'))->firstWhere('id', $entry['id']);
        $this->assertSame('completed', $paid['status']);
        $this->assertFalse($paid['can_change']);
        $this->assertFalse($paid['shares'][0]['can_pay']);
    }

    public function test_period_presets_filter_entries_and_totals_in_group_timezone(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28T12:00:00Z'));
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $dates = ['2026-08-31T12:00:00Z', '2026-09-01T12:00:00Z', '2026-09-22T12:00:00Z',
            '2026-09-28T02:59:00Z', '2026-09-28T03:00:00Z', '2026-09-29T02:59:00Z',
            '2026-09-29T03:00:00Z', '2026-10-01T03:00:00Z', '2026-01-01T02:59:00Z', null];
        foreach ($dates as $index => $date) {
            $this->postJson('/api/recipients/'.$s['recipient']->id.'/entries', ['kind' => 'expense', 'title' => 'Despesa '.$index, 'amount_cents' => ($index + 1) * 100, 'due_at' => $date])->assertCreated();
        }
        foreach (['all' => 5500, 'today' => 2100, 'week' => 3600, 'month' => 3700, 'last_month' => 100, 'last_7' => 2800, 'last_30' => 3100, 'year' => 4600] as $period => $total) {
            $response = $this->getJson('/api/finance?period='.$period)->assertOk()
                ->assertJsonPath('summary.confirmed_cents', $total)->assertJsonPath('summary.unpaid_cents', $total)
                ->assertJsonPath('period.key', $period)->assertJsonPath('period.timezone', 'America/Sao_Paulo');
            $this->assertSame($total, array_sum(array_column($response->json('data'), 'amount_cents')));
        }
        $this->getJson('/api/finance?period=custom&from=2026-09-28&to=2026-09-28')->assertOk()
            ->assertJsonCount(3, 'data')->assertJsonPath('summary.confirmed_cents', 2100);
        $this->getJson('/api/finance?period=custom&from=2020-01-01&to=2020-01-02')->assertOk()
            ->assertJsonCount(0, 'data')->assertJsonPath('summary.confirmed_cents', 0)->assertJsonCount(1, 'recipients');
        $this->getJson('/api/finance?period=custom')->assertUnprocessable();
        $this->getJson('/api/finance?period=custom&from=2026-09-30&to=2026-09-28')->assertUnprocessable();
        $this->getJson('/api/finance?period=unknown')->assertUnprocessable();
    }

    public function test_finance_obeys_area_tenant_and_payment_ownership(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $entry = $this->postJson('/api/recipients/'.$s['recipient']->id.'/entries', ['kind' => 'expense', 'title' => 'Despesa', 'amount_cents' => 1000])->assertCreated()->json();
        $observer = CareScenario::member($s['group'], 'observador');
        $grant = CareScenario::grant($s['recipient'], $observer, ['finance'], false);
        $this->actingAs($observer, 'api')->getJson('/api/finance')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.can_change', false)->assertJsonPath('data.0.shares.0.can_pay', false)->assertJsonPath('recipients.0.can_create', false);
        $grant->update(['areas' => ['routine']]);
        $this->getJson('/api/finance')->assertOk()->assertJsonCount(0, 'data')->assertJsonCount(0, 'recipients')->assertJsonPath('summary.confirmed_cents', 0);
        $other = CareScenario::create('pet');
        $this->actingAs($other['owner'], 'api')->withHeader('X-Tenant', $other['group']->slug)->getJson('/api/finance')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('summary.confirmed_cents', 0);
    }
}
