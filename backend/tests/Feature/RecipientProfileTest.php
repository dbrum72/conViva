<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CareScenario;
use Tests\TestCase;

class RecipientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_is_filtered_in_profile_history_and_rejection_and_access_loss_blocks_approval(): void
    {
        $this->seed();
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['routine', 'health']);
        $observer = CareScenario::member($group, 'cuidador');
        CareScenario::grant($recipient, $observer, ['routine']);
        $path = '/api/recipients/'.$recipient->id;
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $data = ['operation' => 'save', 'version' => 0, 'name' => $recipient->name, 'kind' => 'child', 'routine_profile' => ['school' => 'Escola'], 'health_profile' => ['instructions' => 'Restrito']];
        $id = $this->postJson($path.'/profile-proposals', $data)->assertCreated()->json('id');
        $this->assertNull($recipient->fresh()->health_profile);
        $this->actingAs($observer, 'api')->getJson('/api/decisions/profile/'.$id)->assertOk()->assertJsonMissingPath('payload.data.health_profile')->assertJsonMissing(['after' => ['instructions' => 'Restrito']]);
        $this->actingAs($peer, 'api')->postJson($path.'/profile-proposals/'.$id.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->getJson($path)->assertOk()->assertJsonPath('health_profile.instructions', 'Restrito');
        $this->actingAs($observer, 'api')->getJson($path)->assertOk()->assertJsonMissingPath('health_profile')->assertJsonPath('routine_profile.school', 'Escola');
        $this->getJson('/api/recipients')->assertOk()->assertJsonMissingPath('0.health_profile');
        $this->getJson('/api/notifications')->assertOk()->assertJsonMissing(['instructions' => 'Restrito']);
        $data['version'] = 1;
        $data['health_profile']['instructions'] = 'Nova referência';
        $id = $this->actingAs($owner, 'api')->postJson($path.'/profile-proposals', $data)->assertCreated()->json('id');
        $recipient->accesses()->where('user_id', $peer->id)->update(['areas' => ['routine']]);
        $this->actingAs($peer, 'api')->getJson('/api/decisions/profile/'.$id)->assertOk()->assertJsonPath('can_accept', false);
        $url = $path.'/profile-proposals/'.$id.'/decision';
        $this->postJson($url, ['decision' => 'accepted'])->assertConflict();
        $this->postJson($url, ['decision' => 'rejected', 'reason' => 'Rever'])->assertOk()->assertJsonMissingPath('payload.data.health_profile');
        $this->assertSame('Restrito', $recipient->fresh()->health_profile['instructions']);
    }

    public function test_typed_fields_and_contacts_are_validated(): void
    {
        $this->seed();
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('pet');
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $url = '/api/recipients/'.$recipient->id.'/profile-proposals';
        $base = ['operation' => 'save', 'version' => 0, 'name' => 'Pet', 'kind' => 'pet', 'species' => 'Gato'];
        $this->postJson($url, [...$base, 'routine_profile' => ['school' => 'Escola']])->assertUnprocessable();
        $this->postJson($url, [...$base, 'health_profile' => ['contacts' => [['name' => 'Veterinário']]]])->assertUnprocessable();
        $this->postJson($url, [...$base, 'health_profile' => ['unknown' => 'Inválido']])->assertUnprocessable();
        $this->postJson($url, [...$base, 'routine_profile' => ['identification' => 'Chip 123'], 'health_profile' => ['contacts' => [['name' => 'Veterinário', 'phone' => '123']]]])->assertCreated()->assertJsonPath('status', 'accepted');
        $this->assertSame('Chip 123', $recipient->fresh()->routine_profile['identification']);
    }
}
