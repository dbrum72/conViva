<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_does_not_disclose_accounts_and_sends_a_frontend_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->json();
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.test'])->assertOk()->assertExactJson($known);
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->assertExactJson($known);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith(rtrim(config('conviva.frontend_url'), '/').'/reset-password?', $url);
            $this->assertStringContainsString(urlencode($user->email), $url);
            $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($notification->token, $stored);
            $this->assertTrue(Hash::check($notification->token, $stored));

            return true;
        });
    }

    public function test_reset_changes_password_and_consumes_token_once(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload($user);
        $this->postJson('/api/auth/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->postJson('/api/auth/reset-password', $payload)->assertUnprocessable()->assertJsonValidationErrors('token');
    }

    public function test_mail_transport_failure_keeps_the_same_public_response(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andThrow(new TransportException('SMTP unavailable'));
        Log::shouldReceive('warning')->once()->with('Password recovery mail transport failed.');
        $this->postJson('/api/auth/forgot-password', ['email' => 'transport@example.test'])->assertOk()
            ->assertExactJson(['message' => 'Se houver uma conta com esse e-mail, você receberá as instruções para redefinir sua senha.']);
    }

    public function test_expired_or_mismatched_token_cannot_change_password(): void
    {
        $user = User::factory()->create();
        $original = $user->password;
        $payload = $this->payload($user);
        $this->postJson('/api/auth/reset-password', [...$payload, 'token' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/auth/reset-password', [...$payload, 'email' => 'other@example.test'])->assertUnprocessable();
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
        $this->postJson('/api/auth/reset-password', $payload)->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertSame($original, $user->fresh()->password);
    }

    public function test_confirmation_and_length_are_validated_without_consuming_token(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload($user);
        $this->postJson('/api/auth/reset-password', [...$payload, 'password_confirmation' => 'different'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/auth/reset-password', [...$payload, 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
        $this->postJson('/api/auth/reset-password', $payload)->assertOk();
    }

    public function test_recovery_attempts_are_rate_limited_for_unknown_addresses_too(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password', ['email' => 'limited@example.test'])->assertOk();
        }
        $this->postJson('/api/auth/forgot-password', ['email' => 'limited@example.test'])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_reset_invalidates_existing_jwt_and_new_password_can_login(): void
    {
        $user = User::factory()->create(['password' => 'original-123']);
        $token = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'original-123'])->assertOk()->json('access_token');
        $payload = $this->payload($user);
        $this->postJson('/api/auth/reset-password', $payload)->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->withToken($token)->postJson('/api/auth/refresh')->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->getJson('/api/auth/me?token='.urlencode($token))->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'original-123'])->assertForbidden();
        $newToken = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $payload['password']])->assertOk()->json('access_token');
        app('auth')->forgetGuards();
        $this->withToken($newToken)->getJson('/api/auth/me')->assertOk();
    }

    private function payload(User $user): array
    {
        return ['email' => $user->email, 'token' => Password::createToken($user), 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123'];
    }
}
