<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontOfficeAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'front_office') {
            return $next($request);
        }

        return redirect()->route('dashboard')->with('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }
}
