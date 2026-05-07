<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->group(base_path('routes/provider.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only service; session/cookie middleware is intentionally not used by provider routes.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep default Laravel exception rendering.
    })
    ->create();
