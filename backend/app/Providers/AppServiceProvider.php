<?php

namespace App\Providers;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class, fn () => new CurrentOrganization);
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = rtrim(config('conviva.frontend_url'), '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Redefinir sua senha no conViva')
                ->greeting('Olá!')
                ->line('Recebemos uma solicitação para redefinir sua senha.')
                ->action('Redefinir senha', $url)
                ->line('Este link expira em '.config('auth.passwords.users.expire').' minutos e só pode ser usado uma vez.')
                ->line('Se você não solicitou a alteração, ignore esta mensagem.');
        });
        $response = fn (Request $request, array $headers) => response()->json(['message' => 'Muitas tentativas. Aguarde para tentar novamente.', 'retry_after' => (int) ($headers['Retry-After'] ?? 60)], 429, $headers);
        RateLimiter::for('auth-login', fn (Request $r) => [Limit::perMinute(60)->by('ip:'.$r->ip())->response($response), Limit::perMinute(5)->by(hash('sha256', strtolower((string) $r->input('email')).'|'.$r->ip()))->response($response)]);
        RateLimiter::for('auth-register', fn (Request $r) => Limit::perMinute(5)->by($r->ip())->response($response));
        RateLimiter::for('auth-recovery', fn (Request $r) => [
            Limit::perMinute(10)->by('recovery-ip:'.$r->ip())->response($response),
            Limit::perMinute(3)->by('recovery-email:'.hash('sha256', strtolower(trim((string) $r->input('email')))))->response($response),
        ]);
    }
}
