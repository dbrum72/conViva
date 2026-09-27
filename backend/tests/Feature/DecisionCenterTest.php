<?php

namespace Tests\Feature;

use App\Models\CareProfileProposal;
use App\Models\CareProposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CareScenario;
use Tests\TestCase;

class DecisionCenterTest extends TestCase
{
    use RefreshDatabase;

    private function shared(): array
    {
        $this->seed();
        $scenario = CareScenario::create('child');
        $peer = CareScenario::member($scenario['group'], 'responsavel');
        CareScenario::grant($scenario['recipient'], $peer, ['routine', 'health', 'finance', 'documents']);
        $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $scenario['group']->slug);

        return [...$scenario, 'peer' => $peer, 'path' => '/api/recipients/'.$scenario['recipient']->id];
    }

    public function test_profile_revision_preserves_current_data_until_every_acceptance_and_returns_conflict_for_repeat(): void
    {
        ['recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $recipient->update(['birth_date' => '2020-01-02']);
        $proposal = $this->postJson($path.'/profile-proposals', ['operation' => 'save', 'version' => 0, 'name' => 'Nome revisado', 'kind' => 'child', 'birth_date' => '2020-01-03'])->assertCreated()->json();
        $this->assertSame($recipient->name, $recipient->fresh()->name);
        $this->actingAs($peer, 'api')->getJson('/api/decisions')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.can_accept', true);
        $url = $path.'/profile-proposals/'.$proposal['id'].'/decision';
        $this->postJson($url, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'accepted');
        $this->assertSame('Nome revisado', $recipient->fresh()->name);
        $this->assertSame('2020-01-03', $recipient->fresh()->birth_date->format('Y-m-d'));
        $this->postJson($url, ['decision' => 'accepted'])->assertConflict();
    }

    public function test_rejection_withdrawal_stale_version_and_shared_archival_keep_history(): void
    {
        ['owner' => $owner, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $first = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertCreated()->json('id');
        $this->actingAs($peer, 'api')->postJson($path.'/profile-proposals/'.$first.'/decision', ['decision' => 'rejected'])->assertUnprocessable();
        $this->postJson($path.'/profile-proposals/'.$first.'/decision', ['decision' => 'rejected', 'reason' => 'Ainda precisamos do cadastro.'])->assertOk();
        $this->assertSame('active', $recipient->fresh()->status);
        $this->actingAs($owner, 'api')->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertConflict();
        $second = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 1])->assertCreated()->json('id');
        $this->postJson($path.'/profile-proposals/'.$second.'/withdraw')->assertOk()->assertJsonPath('status', 'withdrawn');
        $third = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 2])->assertCreated()->json('id');
        $this->assertSame('active', $recipient->fresh()->status);
        $this->actingAs($peer, 'api')->postJson($path.'/profile-proposals/'.$third.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->assertSame('archived', $recipient->fresh()->status);
        $this->assertSame(3, CareProfileProposal::count());
    }

    public function test_new_responsible_blocks_open_profile_proposal_without_adding_a_vote(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $id = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertCreated()->json('id');
        $new = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $new, ['routine']);
        $this->actingAs($peer, 'api')->getJson('/api/decisions/profile/'.$id)->assertOk()->assertJsonPath('can_accept', false)->assertJsonCount(1, 'decisions');
        $this->postJson($path.'/profile-proposals/'.$id.'/decision', ['decision' => 'accepted'])->assertConflict();
        $this->assertSame('pending', CareProfileProposal::findOrFail($id)->decisions()->sole()->status);
        $this->actingAs($new, 'api')->postJson($path.'/profile-proposals/'.$id.'/decision', ['decision' => 'accepted'])->assertForbidden();
    }

    public function test_departed_responsible_blocks_care_proposal_but_author_can_withdraw(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $third = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $third, ['routine']);
        $entry = $this->postJson($path.'/entries', ['kind' => 'task', 'title' => 'Cuidado compartilhado'])->assertCreated()->json();
        $id = $entry['proposals'][0]['id'];
        $url = $path.'/entries/'.$entry['id'].'/proposals/'.$id;
        $this->actingAs($peer, 'api')->postJson($url.'/decision', ['decision' => 'accepted'])->assertOk();
        $this->patchJson('/api/organization-members/'.$peer->id.'/status', ['status' => 'inactive'])->assertOk();
        $this->actingAs($third, 'api')->getJson('/api/decisions/entry/'.$id)->assertOk()->assertJsonPath('can_accept', false)->assertJsonCount(2, 'decisions');
        $this->postJson($url.'/decision', ['decision' => 'accepted'])->assertConflict();
        $this->assertSame('pending', CareProposal::findOrFail($id)->status);
        $this->actingAs($owner, 'api')->postJson($url.'/withdraw')->assertOk();
        $this->assertDatabaseHas('care_decisions', ['care_proposal_id' => $id, 'user_id' => $peer->id, 'status' => 'accepted']);
    }

    public function test_author_losing_access_cannot_be_counted_as_current_consent(): void
    {
        ['group' => $group, 'owner' => $owner, 'peer' => $peer, 'path' => $path] = $this->shared();
        $entry = $this->postJson($path.'/entries', ['kind' => 'task', 'title' => 'Cuidado'])->assertCreated()->json();
        $group->users()->updateExistingPivot($owner->id, ['status' => 'inactive']);
        $this->actingAs($peer, 'api')->postJson($path.'/entries/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertConflict();
        $this->assertDatabaseHas('care_entries', ['id' => $entry['id'], 'revision' => 0]);
    }

    public function test_central_filters_pagination_notifications_and_area_isolation(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $entry = $this->postJson($path.'/entries', ['kind' => 'medication', 'title' => 'Saúde privada'])->assertCreated()->json();
        $profile = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertCreated()->json('id');
        $this->getJson('/api/decisions?scope=sent&per_page=1')->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data');
        $this->getJson('/api/decisions?scope=sent&type=profile')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $profile);
        $this->getJson('/api/decisions?per_page=1000')->assertUnprocessable();
        $this->actingAs($peer, 'api')->getJson('/api/notifications')->assertOk()->assertJsonFragment(['destination' => ['type' => 'profile', 'proposal' => $profile]]);
        $observer = CareScenario::member($group, 'observador');
        CareScenario::grant($recipient, $observer, ['routine'], false);
        $this->actingAs($observer, 'api')->getJson('/api/decisions?scope=all')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.can_accept', false);
        $this->getJson('/api/decisions/entry/'.$entry['proposals'][0]['id'])->assertForbidden();
        $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 1])->assertForbidden();
        ['group' => $other, 'owner' => $stranger] = CareScenario::create('pet');
        $this->actingAs($stranger, 'api')->withHeader('X-Tenant', $other->slug)->getJson('/api/decisions/profile/'.$profile)->assertNotFound();
        $this->getJson('/api/decisions?scope=all')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_care_comparison_uses_original_agreement_and_rejection_preserves_it(): void
    {
        ['owner' => $owner, 'peer' => $peer, 'path' => $path] = $this->shared();
        $entry = $this->postJson($path.'/entries', ['kind' => 'task', 'title' => 'Original', 'description' => 'Preservada'])->assertCreated()->json();
        $url = $path.'/entries/'.$entry['id'];
        $this->actingAs($peer, 'api')->postJson($url.'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $changed = $this->actingAs($owner, 'api')->putJson($url, ['kind' => 'task', 'title' => 'Proposto'])->assertOk()->assertJsonPath('title', 'Original')->json();
        $id = $changed['proposals'][0]['id'];
        $this->getJson('/api/decisions/entry/'.$id)->assertOk()->assertJsonFragment(['field' => 'title', 'before' => 'Original', 'after' => 'Proposto'])->assertJsonMissing(['field' => 'description']);
        $this->actingAs($peer, 'api')->postJson($url.'/proposals/'.$id.'/decision', ['decision' => 'rejected', 'reason' => 'Manter original'])->assertOk()->assertJsonPath('title', 'Original');
    }

    public function test_profile_waits_for_all_votes_and_revalidates_previously_accepted_access(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $third = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $third, ['routine']);
        $id = $this->postJson($path.'/profile-proposals', ['operation' => 'archive', 'version' => 0])->assertCreated()->json('id');
        $url = $path.'/profile-proposals/'.$id.'/decision';
        $this->actingAs($peer, 'api')->postJson($url, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'pending');
        $this->assertSame('active', $recipient->fresh()->status);
        $recipient->accesses()->where('user_id', $peer->id)->update(['expires_at' => now()]);
        $this->actingAs($third, 'api')->postJson($url, ['decision' => 'accepted'])->assertConflict();
        $this->assertSame('active', $recipient->fresh()->status);
        $this->assertDatabaseHas('care_profile_decisions', ['care_profile_proposal_id' => $id, 'user_id' => $peer->id, 'status' => 'accepted']);
    }

    public function test_cancellation_includes_responsible_who_joined_after_the_agreement(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient, 'peer' => $peer, 'path' => $path] = $this->shared();
        $entry = $this->postJson($path.'/entries', ['kind' => 'task', 'title' => 'Original'])->assertCreated()->json();
        $this->actingAs($peer, 'api')->postJson($path.'/entries/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $new = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $new, ['routine']);
        $cancel = $this->actingAs($owner, 'api')->deleteJson($path.'/entries/'.$entry['id'])->assertOk()->assertJsonPath('status', 'pending')->json();
        $this->assertCount(2, $cancel['proposals'][0]['decisions']);
        $this->assertDatabaseHas('care_decisions', ['care_proposal_id' => $cancel['proposals'][0]['id'], 'user_id' => $new->id, 'status' => 'pending']);
        $this->putJson($path.'/entries/'.$entry['id'], ['revision' => 0, 'kind' => 'task', 'title' => 'Outra'])->assertConflict();
    }
}
