<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CareScenario;
use Tests\TestCase;

class ResponsibleProfileRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private function shared(): array
    {
        $this->seed();
        $scenario = CareScenario::create('child');
        $invited = CareScenario::member($scenario['group'], 'responsavel');
        CareScenario::grant($scenario['recipient'], $invited, ['routine', 'health', 'finance', 'documents']);
        $this->actingAs($invited, 'api')->withHeader('X-Tenant', $scenario['group']->slug);

        return [...$scenario, 'invited' => $invited, 'path' => '/api/recipients/'.$scenario['recipient']->id];
    }

    private function revision(int $version = 0): array
    {
        return ['operation' => 'save', 'version' => $version, 'name' => 'Nome revisado', 'kind' => 'child',
            'routine_profile' => ['school' => 'Nova escola'], 'health_profile' => ['instructions' => 'Nova referência']];
    }

    public function test_invited_responsible_can_propose_but_all_other_responsibles_must_accept(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient, 'invited' => $invited, 'path' => $path] = $this->shared();
        $third = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $third, ['routine', 'health']);
        $this->getJson('/api/recipients')->assertOk()->assertJsonPath('0.can_manage_profile', true);
        $this->getJson($path)->assertOk()->assertJsonPath('can_manage_profile', true);
        $this->putJson($path, $this->revision())->assertConflict();
        $proposal = $this->postJson($path.'/profile-proposals', $this->revision())->assertCreated()
            ->assertJsonPath('created_by', $invited->id)->assertJsonPath('status', 'pending')->assertJsonCount(2, 'decisions')->json();
        $url = $path.'/profile-proposals/'.$proposal['id'].'/decision';
        $this->assertEqualsCanonicalizing([$owner->id, $third->id], array_column($proposal['decisions'], 'user_id'));
        $this->assertSame($recipient->name, $recipient->fresh()->name);
        $this->actingAs($owner, 'api')->postJson($url, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'pending');
        $this->assertSame($recipient->name, $recipient->fresh()->name);
        $this->assertNull($recipient->fresh()->routine_profile);
        $this->assertNull($recipient->fresh()->health_profile);
        $this->actingAs($third, 'api')->postJson($url, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'accepted');
        $this->assertSame('Nome revisado', $recipient->fresh()->name);
        $this->assertSame('Nova escola', $recipient->fresh()->routine_profile['school']);
        $this->assertSame('Nova referência', $recipient->fresh()->health_profile['instructions']);
        $this->assertSame($owner->id, $recipient->fresh()->created_by);
    }

    public function test_invited_responsible_who_is_now_alone_can_apply_immediately_and_update_directly(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient, 'path' => $path] = $this->shared();
        $group->users()->updateExistingPivot($owner->id, ['status' => 'inactive']);
        $this->postJson($path.'/profile-proposals', $this->revision())->assertCreated()
            ->assertJsonPath('status', 'accepted')->assertJsonCount(0, 'decisions');
        $this->assertSame('Nome revisado', $recipient->fresh()->name);
        $this->putJson($path, ['name' => 'Alteração direta', 'kind' => 'child'])->assertOk();
        $this->assertSame('Alteração direta', $recipient->fresh()->name);
    }

    public function test_rejection_and_withdrawal_of_invited_proposals_preserve_previous_profile(): void
    {
        ['owner' => $owner, 'recipient' => $recipient, 'invited' => $invited, 'path' => $path] = $this->shared();
        $id = $this->postJson($path.'/profile-proposals', $this->revision())->assertCreated()->json('id');
        $this->actingAs($owner, 'api')->postJson($path.'/profile-proposals/'.$id.'/withdraw')->assertForbidden();
        $this->postJson($path.'/profile-proposals/'.$id.'/decision', ['decision' => 'rejected', 'reason' => 'Manter a ficha vigente'])->assertOk();
        $this->assertSame($recipient->name, $recipient->fresh()->name);
        $id = $this->actingAs($invited, 'api')->postJson($path.'/profile-proposals', $this->revision(1))->assertCreated()->json('id');
        $this->postJson($path.'/profile-proposals/'.$id.'/withdraw')->assertOk()->assertJsonPath('status', 'withdrawn');
        $this->assertSame($recipient->name, $recipient->fresh()->name);
    }

    public function test_caregivers_observers_and_expired_responsibles_cannot_change_profile(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'invited' => $invited, 'path' => $path] = $this->shared();
        foreach (['cuidador', 'observador'] as $role) {
            $user = CareScenario::member($group, $role);
            CareScenario::grant($recipient, $user, ['routine', 'health']);
            $this->actingAs($user, 'api')->getJson($path)->assertOk()->assertJsonPath('can_manage_profile', false);
            $this->postJson($path.'/profile-proposals', $this->revision())->assertForbidden();
            $this->putJson($path, $this->revision())->assertForbidden();
        }
        $recipient->accesses()->where('user_id', $invited->id)->update(['expires_at' => now()]);
        $this->actingAs($invited, 'api')->postJson($path.'/profile-proposals', $this->revision())->assertForbidden();
        $this->putJson($path, $this->revision())->assertForbidden();
    }

    public function test_invited_responsible_archival_also_requires_consensus(): void
    {
        ['owner' => $owner, 'recipient' => $recipient, 'path' => $path] = $this->shared();
        $this->deleteJson($path)->assertConflict();
        $id = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertCreated()->json('id');
        $this->assertSame('active', $recipient->fresh()->status);
        $this->actingAs($owner, 'api')->postJson($path.'/profile-proposals/'.$id.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->assertSame('archived', $recipient->fresh()->status);
    }
}
