<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CareScenario;
use Tests\TestCase;

class ResponsibleAccessReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_peer_remains_protected_even_when_access_has_expired(): void
    {
        $this->seed();
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        $access = CareScenario::grant($recipient, $peer, ['health'], false, now()->subDay()->toIso8601String());
        $before = $access->fresh()->toArray();
        $url = '/api/recipients/'.$recipient->id.'/accesses';
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $this->getJson($url)->assertOk()->assertJsonFragment(['user_id' => $peer->id]);
        $this->putJson($url, ['user_id' => $peer->id, 'areas' => ['routine'], 'can_edit' => true])->assertConflict();
        $this->deleteJson($url.'/'.$peer->id)->assertConflict();
        $this->assertSame($before, $access->fresh()->toArray());
    }
}
