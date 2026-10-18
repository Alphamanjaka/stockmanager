<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte archivé pendant qu'il était connecté est déconnecté à sa requête suivante
 * (la connexion d'un compte archivé est déjà refusée par AuthController).
 */
class LogoutArchivedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isArchived()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Votre compte a été désactivé : contactez un administrateur.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
