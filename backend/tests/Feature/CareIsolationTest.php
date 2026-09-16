<?php

namespace Tests\Feature;

use App\Mail\OrganizationInvitationMail;
use App\Models\CareAccess;
use App\Models\CareRecipient;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\ProvisionOrganizationRoles;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CareIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function group(string $slug): Organization
    {
        $g = Organization::create(['name' => $slug, 'slug' => $slug, 'status' => 'active']);
        app(ProvisionOrganizationRoles::class)->execute($g);

        return $g;
    }

    private function member(Organization $g, string $role = 'responsavel'): User
    {
        $u = User::factory()->create();
        $g->users()->attach($u->id, ['status' => 'active', 'joined_at' => now()]);
        setPermissionsTeamId($g->id);
        $u->assignRole(Role::where('organization_id', $g->id)->where('name', $role)->firstOrFail());
        setPermissionsTeamId(null);

        return $u;
    }

    private function recipient(Organization $g, User $u): CareRecipient
    {
        app(CurrentOrganization::class)->clear();

        return CareRecipient::create(['organization_id' => $g->id, 'created_by' => $u->id, 'name' => 'Assistido teste', 'kind' => 'child']);
    }

    private function asMember(User $u, Organization $g): static
    {
        return $this->actingAs($u, 'api')->withHeader('X-Tenant', $g->slug);
    }

    public function test_group_cannot_receive_a_second_recipient_even_after_archival(): void
    {
        $g = $this->group('unico');
        $u = $this->member($g);
        $p = $this->asMember($u, $g)->postJson('/api/recipients', ['name' => 'Ana', 'kind' => 'child'])->assertCreated()->json('id');
        $this->postJson('/api/recipients', ['name' => 'Pedro', 'kind' => 'child'])->assertConflict();
        $this->deleteJson('/api/recipients/'.$p)->assertNoContent();
        $this->postJson('/api/recipients', ['name' => 'Pedro', 'kind' => 'child'])->assertConflict();
        $this->assertDatabaseCount('care_recipients', 1);
    }

    public function test_database_enforces_one_recipient_per_group(): void
    {
        $g = $this->group('unico');
        $u = $this->member($g);
        $this->recipient($g, $u);
        $this->expectException(QueryException::class);
        $this->recipient($g, $u);
    }

    public function test_mother_can_share_two_children_in_separate_groups_with_isolated_participants(): void
    {
        $a = $this->group('filho-a');
        $b = $this->group('filho-b');
        $mother = $this->member($a);
        $b->users()->attach($mother->id, ['status' => 'active', 'joined_at' => now()]);
        setPermissionsTeamId($b->id);
        $mother->unsetRelation('roles')->assignRole(Role::where('organization_id', $b->id)->where('name', 'responsavel')->firstOrFail());
        setPermissionsTeamId(null);
        $pa = $this->recipient($a, $mother);
        $pb = $this->recipient($b, $mother);
        foreach (['responsavel', 'cuidador', 'observador'] as $role) {
            $ua = $this->member($a, $role);
            $ub = $this->member($b, $role);
            foreach ([[$pa, $ua], [$pb, $ub]] as [$p, $u]) {
                CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $u->id, 'areas' => ['routine'], 'can_edit' => $role !== 'observador']);
            }
            $this->asMember($ua, $a)->getJson('/api/recipients')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $pa->id);
            $this->getJson('/api/recipients/'.$pb->id)->assertNotFound();
            $this->asMember($ua, $b)->getJson('/api/recipients')->assertForbidden();
            $this->asMember($ub, $b)->getJson('/api/recipients')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $pb->id);
            $this->asMember($ub, $a)->getJson('/api/recipients')->assertForbidden();
        }
        $this->asMember($mother->fresh(), $a)->getJson('/api/recipients')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $pa->id);
        $this->asMember($mother->fresh(), $b)->getJson('/api/recipients')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $pb->id);
        $this->patchJson('/api/organization-members/'.$mother->id.'/status', ['status' => 'inactive'])->assertOk();
        $this->asMember($mother->fresh(), $a)->getJson('/api/recipients')->assertOk()->assertJsonPath('0.id', $pa->id);
    }

    public function test_registration_creates_an_isolated_care_group(): void
    {
        $r = $this->postJson('/api/auth/register', ['name' => 'Responsável', 'email' => 'owner@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'organization_name' => 'Família']);
        $r->assertCreated()->assertJsonPath('organizations.0.name', 'Família');
        $this->assertDatabaseHas('roles', ['name' => 'responsavel']);
    }

    public function test_all_groups_list_respects_each_role_and_active_access(): void
    {
        $a = $this->group('a');
        $b = $this->group('b');
        $u = $this->member($a);
        $pa = $this->recipient($a, $u);
        $owner = $this->member($b);
        $pb = $this->recipient($b, $owner);
        $b->users()->attach($u->id, ['status' => 'active']);
        setPermissionsTeamId($b->id);
        $u->unsetRelation('roles')->assignRole(Role::where('organization_id', $b->id)->where('name', 'observador')->firstOrFail());
        setPermissionsTeamId(null);
        $grant = CareAccess::create(['care_recipient_id' => $pb->id, 'user_id' => $u->id, 'areas' => ['documents'], 'can_edit' => true]);
        $this->asMember($u->fresh(), $a)->getJson('/api/groups')->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.recipient.id', $pa->id)->assertJsonPath('0.roles', ['responsavel'])
            ->assertJsonPath('0.recipient.capabilities.documents.edit', true)
            ->assertJsonPath('1.recipient.id', $pb->id)->assertJsonPath('1.roles', ['observador'])
            ->assertJsonPath('1.recipient.capabilities.documents.view', true)
            ->assertJsonPath('1.recipient.capabilities.documents.edit', false)
            ->assertJsonPath('1.recipient.capabilities.routine.view', false);
        $this->asMember($u->fresh(), $b)->postJson('/api/recipients/'.$pb->id.'/entries', ['kind' => 'journal', 'title' => 'Negado'])->assertForbidden();
        $grant->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/groups')->assertOk()->assertJsonPath('1.recipient', null);
        $b->users()->updateExistingPivot($u->id, ['status' => 'inactive']);
        $this->getJson('/api/groups')->assertOk()->assertJsonCount(1)->assertJsonPath('0.recipient.id', $pa->id);
        $a->update(['status' => 'inactive']);
        $this->getJson('/api/groups')->assertOk()->assertJsonCount(0);
    }

    public function test_member_of_another_group_cannot_read_a_recipient(): void
    {
        $a = $this->group('grupo-a');
        $b = $this->group('grupo-b');
        $u = $this->member($a);
        $other = $this->member($b);
        $p = $this->recipient($b, $other);
        $this->asMember($u, $a)->getJson('/api/recipients/'.$p->id)->assertNotFound();
        $this->asMember($u, $b)->getJson('/api/recipients')->assertForbidden();
    }

    public function test_caregiver_requires_explicit_access_and_cannot_see_health(): void
    {
        $g = $this->group('grupo');
        $admin = $this->member($g);
        $u = $this->member($g, 'cuidador');
        $p = $this->recipient($g, $admin);
        $this->asMember($u, $g)->getJson('/api/recipients')->assertOk()->assertJsonCount(0);
        $this->asMember($admin, $g)->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'medication', 'title' => 'Registro restrito'])->assertCreated();
        CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $u->id, 'areas' => ['routine'], 'can_edit' => true]);
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id.'/entries')->assertOk()->assertJsonCount(0);
        $this->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'medication', 'title' => 'Não permitido'])->assertForbidden();
        $this->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'task', 'title' => 'Caminhada'])->assertCreated();
    }

    public function test_revoked_expired_and_suspended_access_is_denied(): void
    {
        $g = $this->group('grupo');
        $admin = $this->member($g);
        $u = $this->member($g, 'cuidador');
        $p = $this->recipient($g, $admin);
        $a = CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $u->id, 'areas' => ['routine'], 'can_edit' => true, 'expires_at' => now()->subMinute()]);
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id)->assertForbidden();
        $a->update(['expires_at' => null]);
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id)->assertOk();
        $a->delete();
        $this->getJson('/api/recipients/'.$p->id)->assertForbidden();
        $g->users()->updateExistingPivot($u->id, ['status' => 'inactive']);
        $this->getJson('/api/recipients')->assertForbidden();
    }

    public function test_observer_cannot_write_even_with_an_edit_grant(): void
    {
        $g = $this->group('grupo');
        $admin = $this->member($g);
        $u = $this->member($g, 'observador');
        $p = $this->recipient($g, $admin);
        CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $u->id, 'areas' => ['routine'], 'can_edit' => true]);
        $this->asMember($u, $g)->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'task', 'title' => 'Negado'])->assertForbidden();
    }

    public function test_care_entries_cannot_be_accessed_through_another_recipient(): void
    {
        $g = $this->group('grupo');
        $u = $this->member($g);
        $a = $this->recipient($g, $u);
        $otherGroup = $this->group('outro');
        $b = $this->recipient($otherGroup, $this->member($otherGroup));
        $e = $this->asMember($u, $g)->postJson('/api/recipients/'.$a->id.'/entries', ['kind' => 'task', 'title' => 'Tarefa'])->assertCreated()->json('id');
        $this->patchJson('/api/recipients/'.$b->id.'/entries/'.$e.'/complete')->assertNotFound();
        $this->patchJson('/api/recipients/'.$a->id.'/entries/'.$e.'/complete')->assertOk()->assertJsonPath('status', 'completed');
    }

    public function test_expense_shares_must_match_the_total_and_group(): void
    {
        $g = $this->group('grupo');
        $u = $this->member($g);
        $p = $this->recipient($g, $u);
        $this->asMember($u, $g)->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'expense', 'title' => 'Consulta', 'amount_cents' => 10000, 'shares' => [['user_id' => $u->id, 'amount_cents' => 9000]]])->assertUnprocessable();
        $e = $this->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'expense', 'title' => 'Consulta', 'amount_cents' => 10000])->assertCreated()->json();
        $this->assertSame(10000, $e['shares'][0]['amount_cents']);
        $this->postJson('/api/recipients/'.$p->id.'/entries/'.$e['id'].'/shares/'.$e['shares'][0]['id'].'/pay')->assertOk();
        $this->deleteJson('/api/recipients/'.$p->id.'/entries/'.$e['id'])->assertUnprocessable();
    }

    public function test_documents_are_private_and_require_area_access(): void
    {
        Storage::fake('local');
        $g = $this->group('grupo');
        $admin = $this->member($g);
        $u = $this->member($g, 'cuidador');
        $p = $this->recipient($g, $admin);
        $d = $this->asMember($admin, $g)->postJson('/api/recipients/'.$p->id.'/documents', ['file' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf')])->assertCreated()->assertJsonMissingPath('path')->json('id');
        CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $u->id, 'areas' => ['routine'], 'can_edit' => true]);
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id.'/documents/'.$d.'/download')->assertForbidden();
        $this->asMember($admin, $g)->get('/api/recipients/'.$p->id.'/documents/'.$d.'/download')->assertOk();
    }

    public function test_pet_requires_species_and_accepts_care_records(): void
    {
        $g = $this->group('grupo');
        $u = $this->member($g);
        $this->asMember($u, $g)->postJson('/api/recipients', ['name' => 'Luna', 'kind' => 'pet'])->assertUnprocessable();
        $this->postJson('/api/recipients', ['name' => 'Luna', 'kind' => 'pet', 'species' => 'Gato'])->assertCreated();
    }

    public function test_caregiver_cannot_grant_itself_more_access(): void
    {
        $g = $this->group('grupo');
        $admin = $this->member($g);
        $u = $this->member($g, 'cuidador');
        $p = $this->recipient($g, $admin);
        $this->asMember($u, $g)->putJson('/api/recipients/'.$p->id.'/accesses', ['user_id' => $u->id, 'areas' => ['finance'], 'can_edit' => true])->assertForbidden();
    }

    public function test_existing_user_can_create_another_isolated_group(): void
    {
        $g = $this->group('original');
        $u = $this->member($g);
        $this->recipient($g, $u);
        $new = $this->asMember($u, $g)->postJson('/api/groups', ['name' => 'Segundo grupo'])->assertCreated()->json();
        $this->withHeader('X-Tenant', $new['slug'])->getJson('/api/recipients')->assertOk()->assertJsonCount(0);
        $this->postJson('/api/recipients', ['name' => 'Assistido novo', 'kind' => 'adult'])->assertCreated();
        $this->withHeader('X-Tenant', $g->slug)->getJson('/api/recipients')->assertOk()->assertJsonCount(1);
    }

    public function test_responsible_can_deactivate_their_own_membership(): void
    {
        $g = $this->group('grupo');
        $u = $this->member($g);
        $this->asMember($u, $g)->patchJson('/api/organization-members/'.$u->id.'/status', ['status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('organization_user', ['organization_id' => $g->id, 'user_id' => $u->id, 'status' => 'inactive']);
        $this->asMember($u->fresh(), $g)->getJson('/api/recipients')->assertForbidden();
    }

    public function test_invitation_can_be_revoked_and_no_longer_accepted(): void
    {
        Mail::fake();
        $g = $this->group('grupo');
        $u = $this->member($g);
        $recipient = $this->recipient($g, $u);
        $id = $this->asMember($u, $g)->postJson('/api/organization-invitations', ['email' => 'invited@example.test', 'role' => 'cuidador', 'care_accesses' => [['care_recipient_id' => $recipient->id, 'areas' => ['routine'], 'can_edit' => true]]])->assertCreated()->json('id');
        $token = null;
        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });
        $this->getJson('/api/organization-invitations/accept/'.$token)->assertOk();
        $this->patchJson('/api/organization-invitations/'.$id.'/revoke')->assertOk();
        $this->postJson('/api/organization-invitations/accept/'.$token, ['name' => 'Convidado', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertGone();
    }

    private function sharedCare(): array
    {
        $g = $this->group('compartilhado');
        $a = $this->member($g);
        $b = $this->member($g);
        $p = $this->recipient($g, $a);
        CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $b->id, 'areas' => ['routine', 'health', 'documents', 'finance'], 'can_edit' => true]);

        return [$g, $a, $b, $p];
    }

    public function test_shared_task_requires_peer_acceptance_and_cannot_be_self_approved(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $e = $this->asMember($a, $g)->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'task', 'title' => 'Buscar na escola', 'due_at' => '2026-10-01 10:00:00'])->assertCreated()->assertJsonPath('status', 'awaiting_approval')->json();
        $base = '/api/recipients/'.$p->id.'/entries/'.$e['id'];
        $proposal = $e['proposals'][0]['id'];
        $this->getJson('/api/agenda')->assertOk()->assertJsonCount(0);
        $this->patchJson($base.'/complete')->assertUnprocessable();
        $this->postJson($base.'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertForbidden();
        $this->asMember($b, $g)->postJson($base.'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'pending');
        $this->getJson('/api/agenda')->assertOk()->assertJsonCount(1);
        $this->postJson($base.'/proposals/'.$proposal.'/decision', ['decision' => 'accepted'])->assertConflict();
    }

    public function test_rejection_requires_reason_and_preserves_the_agreed_version(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'event', 'title' => 'Consulta às 10h'])->assertCreated()->json();
        $this->asMember($b, $g)->postJson($path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $change = $this->asMember($a, $g)->putJson($path.'/'.$e['id'], ['kind' => 'event', 'title' => 'Consulta às 18h'])->assertOk()->assertJsonPath('title', 'Consulta às 10h')->json();
        $vote = $path.'/'.$e['id'].'/proposals/'.$change['proposals'][0]['id'].'/decision';
        $this->asMember($b, $g)->postJson($vote, ['decision' => 'rejected', 'reason' => '  '])->assertUnprocessable();
        $this->postJson($vote, ['decision' => 'rejected', 'reason' => 'Não tenho disponibilidade nesse horário.'])->assertOk()->assertJsonPath('title', 'Consulta às 10h')->assertJsonPath('proposals.0.decisions.0.reason', 'Não tenho disponibilidade nesse horário.');
        $this->assertDatabaseCount('care_proposals', 2);
    }

    public function test_peer_cannot_edit_complete_cancel_or_delete_another_authors_document(): void
    {
        Storage::fake('local');
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id;
        $e = $this->asMember($a, $g)->postJson($path.'/entries', ['kind' => 'journal', 'title' => 'Registro próprio'])->assertCreated()->json('id');
        $d = $this->postJson($path.'/documents', ['file' => UploadedFile::fake()->create('arquivo.pdf', 10, 'application/pdf')])->assertCreated()->json('id');
        $this->asMember($b, $g)->putJson($path.'/entries/'.$e, ['kind' => 'journal', 'title' => 'Sobrescrever'])->assertForbidden();
        $this->patchJson($path.'/entries/'.$e.'/complete')->assertForbidden();
        $this->deleteJson($path.'/entries/'.$e)->assertForbidden();
        $this->deleteJson($path.'/documents/'.$d)->assertForbidden();
    }

    public function test_cancellation_of_agreed_task_requires_acceptance_and_preserves_history(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'task', 'title' => 'Rotina'])->assertCreated()->json();
        $this->asMember($b, $g)->postJson($path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk();
        $cancel = $this->asMember($a, $g)->deleteJson($path.'/'.$e['id'])->assertOk()->assertJsonPath('status', 'pending')->json();
        $this->asMember($b, $g)->postJson($path.'/'.$e['id'].'/proposals/'.$cancel['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertDatabaseHas('care_entries', ['id' => $e['id'], 'status' => 'cancelled']);
    }

    public function test_responsible_cannot_remove_suspend_or_demote_a_peer(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $this->asMember($a, $g)->deleteJson('/api/recipients/'.$p->id.'/accesses/'.$b->id)->assertConflict();
        $this->patchJson('/api/organization-members/'.$b->id.'/status', ['status' => 'inactive'])->assertUnprocessable();
        $this->patchJson('/api/organization-members/'.$b->id.'/role', ['role' => 'observador'])->assertUnprocessable();
        $this->putJson('/api/recipients/'.$p->id.'/accesses', ['user_id' => $b->id, 'areas' => ['routine'], 'can_edit' => false])->assertConflict();
    }

    public function test_group_responsible_has_no_automatic_access_to_other_recipients(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $private = $p;
        CareAccess::where('care_recipient_id', $p->id)->delete();
        $this->asMember($b, $g)->getJson('/api/recipients/'.$private->id)->assertForbidden();
        $this->getJson('/api/recipients')->assertOk()->assertJsonCount(0);
    }

    public function test_shared_expense_cannot_impose_debt_or_record_payment_for_another_person(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'expense', 'title' => 'Consulta', 'amount_cents' => 10000, 'shares' => [['user_id' => $a->id, 'amount_cents' => 5000], ['user_id' => $b->id, 'amount_cents' => 5000]]])->assertCreated()->assertJsonCount(0, 'shares')->json();
        $accepted = $this->asMember($b, $g)->postJson($path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertOk()->json();
        $own = collect($accepted['shares'])->firstWhere('user_id', $a->id);
        $other = collect($accepted['shares'])->firstWhere('user_id', $b->id);
        $this->asMember($a, $g)->postJson($path.'/'.$e['id'].'/shares/'.$other['id'].'/pay')->assertForbidden();
        $this->postJson($path.'/'.$e['id'].'/shares/'.$own['id'].'/pay')->assertOk();
    }

    public function test_withdrawal_keeps_previous_acceptances_and_cannot_be_decided_later(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'task', 'title' => 'Proposta'])->assertCreated()->json();
        $base = $path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'];
        $this->postJson($base.'/withdraw')->assertOk()->assertJsonPath('status', 'withdrawn');
        $this->asMember($b, $g)->postJson($base.'/decision', ['decision' => 'accepted'])->assertConflict();
    }

    public function test_caregiver_records_their_own_execution_without_overwriting_the_task(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $carer = $this->member($g, 'cuidador');
        CareAccess::create(['care_recipient_id' => $p->id, 'user_id' => $carer->id, 'areas' => ['routine'], 'can_edit' => true]);
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'task', 'title' => 'Acompanhar passeio', 'assigned_user_id' => $carer->id])->assertCreated()->json();
        $vote = $path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'].'/decision';
        $this->asMember($carer, $g)->postJson($path.'/'.$e['id'].'/execution', [])->assertConflict();
        $this->postJson($vote, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'awaiting_approval');
        $this->asMember($b, $g)->postJson($vote, ['decision' => 'accepted'])->assertOk()->assertJsonPath('status', 'pending');
        $this->asMember($carer, $g)->postJson($path.'/'.$e['id'].'/execution', ['description' => 'Passeio realizado.'])->assertCreated()->assertJsonPath('created_by', $carer->id)->assertJsonPath('related_entry_id', $e['id']);
        $this->postJson($path.'/'.$e['id'].'/execution', [])->assertConflict();
        $this->assertDatabaseHas('care_entries', ['id' => $e['id'], 'created_by' => $a->id, 'status' => 'pending']);
    }

    public function test_document_only_observer_does_not_gain_access_to_routine(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $observer = $this->member($g, 'observador');
        $this->asMember($a, $g)->putJson('/api/recipients/'.$p->id.'/accesses', ['user_id' => $observer->id, 'areas' => ['documents'], 'can_edit' => true])->assertCreated()->assertJsonPath('can_edit', false)->assertJsonPath('areas', ['documents']);
        $this->asMember($observer, $g)->getJson('/api/recipients/'.$p->id)->assertOk()->assertJsonPath('capabilities.routine.view', false)->assertJsonPath('capabilities.documents.view', true);
        $this->getJson('/api/recipients')->assertOk()->assertJsonCount(1);
        $this->postJson('/api/recipients/'.$p->id.'/entries', ['kind' => 'journal', 'title' => 'Não permitido'])->assertForbidden();
    }

    public function test_suspended_approver_cannot_be_silently_bypassed(): void
    {
        [$g,$a,$b,$p] = $this->sharedCare();
        $path = '/api/recipients/'.$p->id.'/entries';
        $e = $this->asMember($a, $g)->postJson($path, ['kind' => 'task', 'title' => 'Rotina'])->assertCreated()->json();
        $g->users()->updateExistingPivot($b->id, ['status' => 'inactive']);
        $this->asMember($b, $g)->postJson($path.'/'.$e['id'].'/proposals/'.$e['proposals'][0]['id'].'/decision', ['decision' => 'accepted'])->assertForbidden();
        $this->assertDatabaseHas('care_entries', ['id' => $e['id'], 'status' => 'awaiting_approval']);
    }

    public function test_responsible_can_change_their_own_role_and_loses_management_permissions(): void
    {
        $g = $this->group('grupo');
        $u = $this->member($g);
        $other = $this->member($g, 'cuidador');
        $this->asMember($u, $g)->patchJson('/api/organization-members/'.$u->id.'/role', ['role' => 'observador'])->assertOk();
        $this->asMember($u->fresh(), $g)->getJson('/api/auth/context')->assertOk()->assertJsonFragment(['observador']);
        $this->patchJson('/api/organization-members/'.$other->id.'/status', ['status' => 'inactive'])->assertForbidden();
    }

    public function test_member_actions_are_available_only_for_self_or_non_responsible_members(): void
    {
        $g = $this->group('grupo');
        $a = $this->member($g);
        $b = $this->member($g);
        $carer = $this->member($g, 'cuidador');
        $rows = collect($this->asMember($a, $g)->getJson('/api/organization-members')->assertOk()->json());
        $this->assertTrue($rows->firstWhere('id', $a->id)['can_update_role']);
        $this->assertTrue($rows->firstWhere('id', $a->id)['can_update_status']);
        $this->assertFalse($rows->firstWhere('id', $b->id)['can_update_role']);
        $this->assertFalse($rows->firstWhere('id', $b->id)['can_update_status']);
        $this->assertTrue($rows->firstWhere('id', $carer->id)['can_update_role']);
        $this->assertTrue($rows->firstWhere('id', $carer->id)['can_update_status']);
        $this->patchJson('/api/organization-members/'.$carer->id.'/status', ['status' => 'inactive'])->assertOk();
    }

    private function careInvitation(string $role = 'responsavel', array $areas = ['routine'], bool $edit = true): array
    {
        Mail::fake();
        $g = $this->group('convite');
        $a = $this->member($g);
        $p = $this->recipient($g, $a);
        $otherGroup = $this->group('outro');
        $other = $this->recipient($otherGroup, $this->member($otherGroup));
        $this->asMember($a, $g)->postJson('/api/organization-invitations', ['email' => 'new-care@example.test', 'role' => $role, 'care_accesses' => [['care_recipient_id' => $p->id, 'areas' => $areas, 'can_edit' => $edit]]])->assertCreated();
        $token = null;
        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });
        app(CurrentOrganization::class)->clear();
        setPermissionsTeamId(null);

        return [$g, $a, $p, $other, $token];
    }

    public function test_new_invited_responsible_sees_only_selected_recipient_after_login(): void
    {
        [$g,$a,$p,$other,$token] = $this->careInvitation();
        $result = $this->postJson('/api/organization-invitations/accept/'.$token, ['name' => 'Novo responsável', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertOk()->json();
        $this->assertDatabaseHas('care_accesses', ['care_recipient_id' => $p->id, 'user_id' => $result['user']['id'], 'can_edit' => true]);
        $this->app['auth']->forgetGuards();
        $login = $this->postJson('/api/auth/login', ['email' => 'new-care@example.test', 'password' => 'password123'])->assertOk()->json();
        $this->app['auth']->forgetGuards();
        $this->withToken($login['access_token'])->withHeader('X-Tenant', $g->slug)->getJson('/api/recipients')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $p->id);
        $this->getJson('/api/recipients/'.$p->id)->assertOk()->assertJsonPath('capabilities.health.edit', true);
        $this->getJson('/api/recipients/'.$other->id)->assertNotFound();
    }

    public function test_invited_observer_receives_only_selected_area_and_never_edit_access(): void
    {
        [$g,$a,$p,$other,$token] = $this->careInvitation('observador', ['documents'], true);
        $r = $this->postJson('/api/organization-invitations/accept/'.$token, ['name' => 'Observador', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertOk()->json();
        $u = User::findOrFail($r['user']['id']);
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id)->assertOk()->assertJsonPath('capabilities.documents.view', true)->assertJsonPath('capabilities.documents.edit', false)->assertJsonPath('capabilities.routine.view', false);
    }

    public function test_acceptance_links_an_existing_account_without_creating_duplicate_user(): void
    {
        [$g,$a,$p,$other,$token] = $this->careInvitation('cuidador', ['routine'], true);
        $u = User::factory()->create(['email' => 'new-care@example.test']);
        $this->postJson('/api/organization-invitations/accept/'.$token)->assertOk()->assertJsonPath('user.id', $u->id)->assertJsonMissingPath('access_token');
        $this->asMember($u, $g)->getJson('/api/recipients/'.$p->id)->assertOk()->assertJsonPath('capabilities.routine.edit', true)->assertJsonPath('capabilities.health.view', false);
        $this->assertEquals(1, User::where('email', 'new-care@example.test')->count());
    }

    public function test_invitation_cannot_include_assistido_from_another_group_or_no_assistido(): void
    {
        Mail::fake();
        $a = $this->group('a');
        $b = $this->group('b');
        $u = $this->member($a);
        $other = $this->member($b);
        $p = $this->recipient($b, $other);
        $this->asMember($u, $a)->postJson('/api/organization-invitations', ['email' => 'scope@example.test', 'role' => 'responsavel', 'care_accesses' => [['care_recipient_id' => $p->id, 'areas' => ['routine'], 'can_edit' => true]]])->assertUnprocessable();
        $this->postJson('/api/organization-invitations', ['email' => 'scope@example.test', 'role' => 'responsavel', 'care_accesses' => []])->assertUnprocessable();
        Mail::assertNothingSent();
    }

    public function test_acceptance_rechecks_inviter_access_and_rolls_back_registration_on_failure(): void
    {
        [$g,$a,$p,$other,$token] = $this->careInvitation();
        $g->users()->updateExistingPivot($a->id, ['status' => 'inactive']);
        $this->postJson('/api/organization-invitations/accept/'.$token, ['name' => 'Convidado', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'new-care@example.test']);
        $this->assertDatabaseHas('organization_invitations', ['email' => 'new-care@example.test', 'status' => 'pending']);
    }
}
