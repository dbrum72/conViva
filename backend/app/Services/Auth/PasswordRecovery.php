<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordRecovery
{
    public function requestLink(array $credentials): void
    {
        // Do not expose whether the address exists or already has a recent link.
        try {
            Password::sendResetLink($credentials);
        } catch (TransportExceptionInterface) {
            // A transport failure must not reveal that this address has an account.
            Log::warning('Password recovery mail transport failed.');
        }
    }

    public function reset(array $credentials): void
    {
        $status = DB::transaction(function () use ($credentials) {
            // Serialize token consumption, including simultaneous reset requests.
            DB::table(config('auth.passwords.users.table'))
                ->where('email', $credentials['email'])->lockForUpdate()->first();

            return Password::reset($credentials, function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Este link é inválido ou expirou. Solicite um novo link de recuperação.',
            ]);
        }
    }
}
