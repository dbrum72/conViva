<?php

namespace App\Providers;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class, fn () => new CurrentOrganization);
    }
}
