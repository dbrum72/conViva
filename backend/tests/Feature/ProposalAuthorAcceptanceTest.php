<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class ProposalAuthorAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_author_consent_is_explicit_for_care_and_profile_without_accepting_for_peers(): void
    {
        $this->seed();
        $s = CareScenario::create('child');
        $peer = CareScenario::member($s['group'], 'responsavel');
        CareScenario::grant($s['recipient'], $peer, ['routine', 'health']);
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $path = '/api/recipients/'.$s['recipient']->id;
        $entry = $this->postJson($path.'/entries', ['kind' => 'medication', 'title' => 'Vermífugo'])->assertCreated()->json();
        $profile = $this->postJson($path.'/profile-proposals', ['operation' => 'save', 'version' => 0, 'name' => 'Nome revisado', 'kind' => 'child'])->assertCreated()->json();
        foreach (['entry' => $entry['proposals'][0]['id'], 'profile' => $profile['id']] as $type => $id) {
            $detail = $this->getJson('/api/decisions/'.$type.'/'.$id)->assertOk()
                ->assertJsonPath('author_acceptance.status', 'accepted')
                ->assertJsonPath('status', 'pending')
                ->assertJsonPath('can_accept', false)
                ->assertJsonCount(1, 'decisions')
                ->assertJsonPath('decisions.0.user_id', $peer->id)
                ->assertJsonPath('decisions.0.status', 'pending');
            $this->assertNotNull($detail->json('author_acceptance.decided_at'));
        }
        $this->getJson('/api/decisions?scope=mine')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/decisions?scope=all')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($peer, 'api')->getJson('/api/decisions?scope=mine')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson($path.'/entries/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'pending');
        $this->getJson('/api/decisions/entry/'.$entry['proposals'][0]['id'])->assertOk()->assertJsonPath('status', 'accepted')->assertJsonPath('author_acceptance.status', 'accepted');
    }
}
