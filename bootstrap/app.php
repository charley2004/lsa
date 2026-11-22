<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs before the session starts, so cached public pages skip the DB entirely.
        $middleware->web(prepend: [
            \App\Http\Middleware\CachePublicPage::class,
        ]);

        $middleware->alias([
            'verify.sync-key' => \App\Http\Middleware\VerifySyncKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
