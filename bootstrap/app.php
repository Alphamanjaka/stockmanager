<?php

use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\EnsureFrontOfficeAccess;
use App\Http\Middleware\LogoutArchivedUser;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enregistre le middleware pour toutes les requêtes web
        $middleware->web(append: [
            SetLocaleMiddleware::class,
            // Compte archivé pendant sa session : déconnecté
            LogoutArchivedUser::class,
        ]);

        $middleware->alias([
            'ensure.back.office' => EnsureBackOfficeAccess::class,
            'ensure.front.office' => EnsureFrontOfficeAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
