<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareBaselineScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_shared_child_profile_cannot_be_changed_or_archived_by_its_author(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['routine', 'health', 'documents', 'finance']);

        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $this->putJson('/api/recipients/'.$recipient->id, ['name' => 'Alterado', 'kind' => 'child'])->assertConflict();
        $this->deleteJson('/api/recipients/'.$recipient->id)->assertConflict();
        $this->assertDatabaseHas('care_recipients', ['id' => $recipient->id, 'name' => $recipient->name, 'status' => 'active']);
    }

    public function test_temporary_adult_caregiver_cannot_execute_or_read_notifications_after_expiration(): void
    {
        $this->travelTo(now()->startOfSecond());
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('adult');
        $carer = CareScenario::member($group, 'cuidador');
        CareScenario::grant($recipient, $carer, ['routine'], true, now()->addHour()->toIso8601String());
        $path = '/api/recipients/'.$recipient->id.'/entries';

        $entry = $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug)
            ->postJson($path, ['kind' => 'task', 'title' => 'Acompanhar refeição', 'assigned_user_id' => $carer->id])
            ->assertCreated()->json();
        $this->actingAs($carer, 'api')->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])
            ->assertOk()->assertJsonPath('status', 'pending');
        $notifications = $this->getJson('/api/notifications')->assertOk()->json();
        $this->assertNotEmpty($notifications);

        $this->travel(1)->hours();
        $this->postJson($path.'/'.$entry['id'].'/execution', ['description' => 'Tentativa após expiração'])->assertForbidden();
        $this->getJson('/api/notifications')->assertOk()->assertExactJson([]);
        $this->postJson('/api/notifications/'.$notifications[0]['id'].'/read')->assertForbidden();
        $this->assertDatabaseMissing('care_entries', ['related_entry_id' => $entry['id']]);
    }

    public function test_pet_caregiver_cannot_inherit_responsible_permissions_from_child_group(): void
    {
        ['owner' => $user] = CareScenario::create('child');
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('pet');
        CareScenario::member($group, 'cuidador', $user);
        CareScenario::grant($recipient, $user, ['routine']);
        $path = '/api/recipients/'.$recipient->id.'/entries';

        $entry = $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug)
            ->postJson($path, ['kind' => 'feeding', 'title' => 'Alimentação do pet', 'assigned_user_id' => $user->id])
            ->assertCreated()->json();
        $this->actingAs($user->fresh(), 'api')->getJson('/api/recipients/'.$recipient->id)
            ->assertOk()->assertJsonPath('capabilities.routine.edit', true)
            ->assertJsonPath('capabilities.health.view', false)->assertJsonPath('can_manage_access', false);
        $this->postJson($path, ['kind' => 'medication', 'title' => 'Saúde não autorizada'])->assertForbidden();
        $this->postJson($path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $this->postJson($path.'/'.$entry['id'].'/execution', ['description' => 'Alimentação realizada'])
            ->assertCreated()->assertJsonPath('created_by', $user->id)->assertJsonPath('related_entry_id', $entry['id']);
        $this->assertDatabaseHas('care_entries', ['id' => $entry['id'], 'created_by' => $owner->id, 'status' => 'pending']);
    }
}
