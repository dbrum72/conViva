<?php

namespace Tests\Feature;

use App\Modules\Notifications\DeliverReminders;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CareScenario;
use Tests\TestCase;

class ReminderDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_opt_in_quiet_hours_idempotency_and_execution_are_respected(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2026, 11, 14)->setTime(13, 0));
        $scenario = CareScenario::create('child');
        $owner = $scenario['owner'];
        $group = $scenario['group']->fresh();
        $recipient = $scenario['recipient'];
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $url = '/api/notifications/preferences';
        $this->getJson($url)->assertOk()->assertJsonPath('internal', false);
        $entry = $this->postJson('/api/recipients/'.$recipient->id.'/entries', [
            'kind' => 'task', 'title' => 'Cuidado', 'publish_to_agenda' => true,
            'due_at' => now()->addMinutes(30)->toISOString(),
        ])->assertCreated()->json();
        $date = now()->setTimezone($group->timezone)->toDateString();
        $occurrence = $this->getJson('/api/agenda?from='.$date.'&to='.$date)->assertOk()->json('data.0.id');
        $context = app(CurrentOrganization::class);
        $context->clear();
        $job = new DeliverReminders($group->id, $owner->id);
        app()->call([$job, 'handle']);
        $this->assertDatabaseCount('notification_reminder_deliveries', 0);
        $settings = ['internal' => true, 'email' => true, 'third_party' => false, 'summary' => false, 'advance' => 60];
        $this->putJson($url, [...$settings, 'quiet_start' => '09:00', 'quiet_end' => '11:00'])->assertOk();
        Mail::getSymfonyTransport()->flush();
        app()->call([$job, 'handle']);
        $this->assertDatabaseCount('notification_reminder_deliveries', 0);
        $this->putJson($url, [...$settings, 'quiet_start' => null, 'quiet_end' => null])->assertOk();
        $context->clear();
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);
        $this->assertFalse($context->has());
        $this->assertDatabaseCount('notification_reminder_deliveries', 2);
        $this->assertCount(1, Mail::getSymfonyTransport()->messages());
        $this->getJson('/api/notifications?page=1&per_page=1')->assertOk()->assertJsonStructure(['data', 'current_page', 'last_page']);
        $this->postJson('/api/occurrences/'.$occurrence.'/execution', ['occurred_at' => now()->toISOString()])->assertCreated();
        $this->travel(2)->hours();
        app()->call([$job, 'handle']);
        $this->assertDatabaseCount('notification_reminder_deliveries', 2);

        $observer = CareScenario::member($group, 'observador');
        $grant = CareScenario::grant($recipient, $observer, ['routine'], false);
        $this->actingAs($observer, 'api')->putJson($url, [...$settings, 'third_party' => true])->assertOk();
        $queued = new DeliverReminders($group->id, $observer->id);
        $grant->delete();
        app()->call([$queued, 'handle']);
        $this->assertDatabaseCount('notification_reminder_deliveries', 2);
    }
}
