<?php

namespace Tests\Feature;

use App\Models\CareRecipientAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CareScenario;
use Tests\TestCase;

class RecipientAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_each_responsible_has_an_independent_private_photo(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['routine']);
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $this->getJson('/api/auth/context')->assertJsonPath('organization.can_manage_avatar', true);
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('owner.jpg'), 'user_id' => $peer->id])->assertNoContent();
        $ownerPath = CareRecipientAvatar::where('user_id', $owner->id)->sole()->path;
        $this->get('/api/my-recipient-avatar')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($peer, 'api')->get('/api/my-recipient-avatar')->assertNoContent();
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('peer.png')])->assertNoContent();
        $peerPath = CareRecipientAvatar::where('user_id', $peer->id)->sole()->path;
        $response = $this->get('/api/my-recipient-avatar')->assertOk();
        $this->assertSame(Storage::disk('local')->get($peerPath), $response->streamedContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('replacement.jpg')])->assertNoContent();
        Storage::disk('local')->assertMissing($peerPath);
        Storage::disk('local')->assertExists($ownerPath);
        $this->deleteJson('/api/my-recipient-avatar')->assertNoContent();
        $this->get('/api/my-recipient-avatar')->assertNoContent();
        Storage::disk('local')->assertExists($ownerPath);
        $this->assertDatabaseCount('care_recipient_avatars', 1);
        $this->assertDatabaseHas('care_recipients', ['id' => $recipient->id, 'name' => $recipient->name]);
    }

    public function test_tenant_role_membership_and_recipient_access_are_required(): void
    {
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        $access = CareScenario::grant($recipient, $peer, ['routine'], true, now()->subMinute()->toIso8601String());
        $this->actingAs($peer, 'api')->withHeader('X-Tenant', $group->slug);
        $this->get('/api/my-recipient-avatar')->assertForbidden();
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('photo.jpg')])->assertForbidden();
        $this->deleteJson('/api/my-recipient-avatar')->assertForbidden();
        $access->update(['expires_at' => null]);
        $this->get('/api/my-recipient-avatar')->assertNoContent();
        $group->users()->updateExistingPivot($peer->id, ['status' => 'inactive']);
        $this->get('/api/my-recipient-avatar')->assertForbidden();

        $observer = CareScenario::member($group, 'observador');
        CareScenario::grant($recipient, $observer, ['routine'], false);
        $this->actingAs($observer, 'api')->getJson('/api/auth/context')->assertJsonPath('organization.can_manage_avatar', false);
        $this->get('/api/my-recipient-avatar')->assertForbidden();
        ['group' => $other] = CareScenario::create('pet');
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $other->slug)->get('/api/my-recipient-avatar')->assertForbidden();
        CareScenario::member($other, 'responsavel', $owner);
        // Membership alone does not grant access to the other recipient.
        $this->get('/api/my-recipient-avatar')->assertForbidden();
    }

    public function test_same_user_keeps_different_photos_in_each_group(): void
    {
        ['group' => $first, 'owner' => $user] = CareScenario::create('child');
        ['group' => $second, 'recipient' => $recipient] = CareScenario::create('pet');
        CareScenario::member($second, 'responsavel', $user);
        CareScenario::grant($recipient, $user, ['routine']);
        $this->actingAs($user, 'api')->withHeader('X-Tenant', $first->slug);
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('child.jpg')])->assertNoContent();
        $firstPath = CareRecipientAvatar::sole()->path;
        $this->withHeader('X-Tenant', $second->slug)->get('/api/my-recipient-avatar')->assertNoContent();
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('pet.png')])->assertNoContent();
        $this->assertDatabaseCount('care_recipient_avatars', 2);
        $this->deleteJson('/api/my-recipient-avatar')->assertNoContent();
        $response = $this->withHeader('X-Tenant', $first->slug)->get('/api/my-recipient-avatar')->assertOk();
        $this->assertSame(Storage::disk('local')->get($firstPath), $response->streamedContent());
    }

    public function test_invalid_uploads_do_not_replace_the_current_photo(): void
    {
        ['group' => $group, 'owner' => $owner] = CareScenario::create('child');
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $this->postJson('/api/my-recipient-avatar', ['image' => UploadedFile::fake()->image('valid.png')])->assertNoContent();
        $path = CareRecipientAvatar::sole()->path;
        foreach ([
            UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->create('fake.jpg', 1, 'text/plain'),
            UploadedFile::fake()->image('large.jpg')->size(2049),
            UploadedFile::fake()->image('wide.png', 4097, 1),
        ] as $file) {
            $this->postJson('/api/my-recipient-avatar', ['image' => $file])->assertUnprocessable()->assertJsonValidationErrors('image');
        }
        $this->assertSame($path, CareRecipientAvatar::sole()->path);
        Storage::disk('local')->assertExists($path);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }
}
