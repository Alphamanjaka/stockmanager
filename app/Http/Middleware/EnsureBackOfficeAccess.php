<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackOfficeAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'back_office') {
            return $next($request);
        }

        // Les appels AJAX/API attendent un code HTTP, pas une redirection vers une page HTML.
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }
}
