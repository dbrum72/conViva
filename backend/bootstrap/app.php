<?php

use App\Http\Middleware\ResolveOrganization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Log;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',
    )
    ->withMiddleware(
        function (
            Middleware $middleware
        ): void {
            $middleware->alias([
                'tenant' => ResolveOrganization::class,
            ]);

            $middleware
                ->prependToPriorityList(
                    SubstituteBindings::class,
                    ResolveOrganization::class,
                );
        }
    )
    ->withExceptions(
        function (
            Exceptions $exceptions
        ): void {
            $exceptions->report(function (Throwable $exception) {
                if (! request()->is('api/*') && ! $exception instanceof Illuminate\Database\QueryException) {
                    return null;
                }

                Log::error('Falha operacional.', [
                    'type' => $exception::class,
                    'file' => basename($exception->getFile()),
                    'line' => $exception->getLine(),
                ]);

                return false;
            });
            $exceptions
                ->shouldRenderJsonWhen(
                    fn (
                        Request $request
                    ) => $request->is(
                        'api/*'
                    ),
                );
        }
    )
    ->create();
