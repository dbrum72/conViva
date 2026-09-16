<?php

namespace App\Providers;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $response = fn (Request $request, array $headers) => response()->json(['message' => 'Muitas tentativas. Aguarde para tentar novamente.', 'retry_after' => (int) ($headers['Retry-After'] ?? 60)], 429, $headers);
        RateLimiter::for('auth-login', fn (Request $r) => [Limit::perMinute(60)->by('ip:'.$r->ip())->response($response), Limit::perMinute(5)->by(hash('sha256', strtolower((string) $r->input('email')).'|'.$r->ip()))->response($response)]);
        RateLimiter::for('auth-register', fn (Request $r) => Limit::perMinute(5)->by($r->ip())->response($response));
    }
}
