<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enregistre le middleware pour toutes les requêtes web
        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleMiddleware::class,
        ]);

        $middleware->alias([
            'ensure.back.office' => \App\Http\Middleware\EnsureBackOfficeAccess::class,
            'ensure.front.office' => \App\Http\Middleware\EnsureFrontOfficeAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
