<?php

namespace Tests\Feature;

use App\Models\CareNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class NotificationCountTest extends TestCase
{
    use DatabaseTransactions;

    public function test_count_includes_all_unread_authorized_notifications_and_updates_after_reading(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $observer = CareScenario::member($s['group'], 'observador');
        $grant = CareScenario::grant($s['recipient'], $observer, ['routine'], false);
        $base = ['organization_id' => $s['group']->id, 'care_recipient_id' => $s['recipient']->id, 'user_id' => $observer->id, 'area' => 'routine', 'message' => 'Teste'];
        for ($i = 0; $i < 105; $i++) {
            $notification = CareNotification::create($base);
        }
        CareNotification::create([...$base, 'read_at' => now()]);
        CareNotification::create([...$base, 'area' => 'health']);
        CareNotification::create([...$base, 'user_id' => $s['owner']->id]);
        $this->actingAs($observer, 'api')->withHeader('X-Tenant', $s['group']->slug);
        $this->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('unread_count', 105);
        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(100);
        $this->postJson('/api/notifications/'.$notification->id.'/read')->assertNoContent();
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('unread_count', 104);
        $this->postJson('/api/notifications/'.$notification->id.'/read')->assertNoContent();
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('unread_count', 104);
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('unread_count', 0);
        $this->getJson('/api/notifications')->assertJsonCount(0);
    }

    public function test_count_is_isolated_by_group_even_for_the_same_user(): void
    {
        $this->seed();
        $first = CareScenario::create('child');
        $second = CareScenario::create('pet');
        CareScenario::member($second['group'], 'observador', $first['owner']);
        CareScenario::grant($second['recipient'], $first['owner'], ['routine'], false);
        CareNotification::create(['organization_id' => $first['group']->id, 'care_recipient_id' => $first['recipient']->id, 'user_id' => $first['owner']->id, 'area' => 'routine', 'message' => 'Primeiro grupo']);
        $this->actingAs($first['owner'], 'api')->withHeader('X-Tenant', $first['group']->slug);
        $this->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('unread_count', 1);
        $this->withHeader('X-Tenant', $second['group']->slug)->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('unread_count', 0);
    }
}
