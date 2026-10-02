<?php

namespace Tests\Feature;

use App\Mail\OrganizationInvitationMail;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\CareScenario;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private function invitation(): array
    {
        $this->seed();
        Mail::fake();
        $scenario = CareScenario::create('pet');
        $email = 'invitation-'.Str::uuid().'@example.test';
        $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $scenario['group']->slug)
            ->postJson('/api/organization-invitations', [
                'email' => $email,
                'role' => 'responsavel',
                'care_accesses' => [[
                    'care_recipient_id' => $scenario['recipient']->id,
                    'areas' => ['routine', 'health', 'documents', 'finance'],
                    'can_edit' => true,
                ]],
            ])->assertCreated();
        $mail = Mail::sent(OrganizationInvitationMail::class)->sole();
        $this->assertSame(rtrim(config('conviva.frontend_url'), '/').'/invitations/accept/'.$mail->token, $mail->acceptanceUrl);
        $this->assertStringContainsString($mail->acceptanceUrl, $mail->render());
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        app(CurrentOrganization::class)->clear();
        setPermissionsTeamId(null);

        return [...$scenario, 'email' => $email, 'invitation' => $mail->invitation,
            'url' => '/api/organization-invitations/accept/'.$mail->token];
    }

    private function registration(): array
    {
        return ['name' => 'Convidado sintético', 'password' => 'Synthetic-password-123', 'password_confirmation' => 'Synthetic-password-123'];
    }

    public function test_new_account_accepts_once_and_can_access_the_invited_recipient(): void
    {
        ['url' => $url, 'email' => $email, 'group' => $group, 'recipient' => $recipient] = $this->invitation();
        $this->getJson($url)->assertOk()->assertJsonPath('registration_required', true);
        $result = $this->postJson($url, $this->registration())->assertOk()->assertJsonStructure(['access_token', 'user', 'organization'])->json();
        $this->assertDatabaseHas('organization_user', ['organization_id' => $group->id, 'user_id' => $result['user']['id'], 'status' => 'active']);
        $this->assertDatabaseHas('care_accesses', ['care_recipient_id' => $recipient->id, 'user_id' => $result['user']['id'], 'can_edit' => true]);
        $this->getJson($url)->assertGone();
        $this->postJson($url, $this->registration())->assertGone();
        $this->app['auth']->forgetGuards();
        $this->withToken($result['access_token'])->withHeader('X-Tenant', $group->slug)
            ->getJson('/api/recipients/'.$recipient->id)->assertOk()->assertJsonPath('capabilities.health.edit', true);
        $this->assertSame(1, User::where('email', $email)->count());
    }

    public function test_existing_account_accepts_without_registration_or_duplicate(): void
    {
        ['url' => $url, 'email' => $email, 'recipient' => $recipient] = $this->invitation();
        $user = User::factory()->create(['email' => $email]);
        $password = $user->password;
        $this->getJson($url)->assertOk()->assertJsonPath('registration_required', false);
        $this->postJson($url)->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('access_token');
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame(1, User::where('email', $email)->count());
        $this->assertDatabaseHas('care_accesses', ['care_recipient_id' => $recipient->id, 'user_id' => $user->id]);
    }

    public function test_expired_and_revoked_invitations_cannot_be_accepted(): void
    {
        ['url' => $url, 'email' => $email, 'invitation' => $invitation] = $this->invitation();
        $invitation->update(['expires_at' => now()->subMinute()]);
        $this->getJson($url)->assertGone();
        $this->postJson($url, $this->registration())->assertGone();
        $invitation->update(['expires_at' => now()->addDay(), 'status' => OrganizationInvitation::STATUS_REVOKED]);
        $this->getJson($url)->assertGone();
        $this->postJson($url, $this->registration())->assertGone();
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_invalid_registration_can_be_corrected_without_consuming_invitation(): void
    {
        ['url' => $url, 'email' => $email, 'invitation' => $invitation] = $this->invitation();
        $this->postJson($url, [...$this->registration(), 'password_confirmation' => 'different'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->postJson($url, $this->registration())->assertOk();
    }

    public function test_loss_of_inviter_access_rolls_back_account_and_membership(): void
    {
        ['url' => $url, 'email' => $email, 'invitation' => $invitation, 'group' => $group, 'owner' => $owner] = $this->invitation();
        $group->users()->updateExistingPivot($owner->id, ['status' => 'inactive']);
        $this->postJson($url, $this->registration())->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertSame('pending', $invitation->fresh()->status);
    }
}
