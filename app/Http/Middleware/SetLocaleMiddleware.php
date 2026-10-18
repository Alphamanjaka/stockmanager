<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue de l'application : réglage "app_locale" (Administration › Paramètres › Interface & System),
 * commun à tous les utilisateurs. Sans réglage, ou s'il n'est plus proposé, APP_LOCALE s'applique.
 */
class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = settings('app_locale');

        if (array_key_exists((string) $locale, config('app.supported_locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
