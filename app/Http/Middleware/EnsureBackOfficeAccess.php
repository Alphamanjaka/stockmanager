<?php

namespace App\Http\Middleware;

use App\Core\Support\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back office (routes admin.php et api.php des modules) : la route doit être ouverte au rôle du compte
 * (App\Core\Support\Role). Un export se vérifie sous le nom « admin.exports.<liste> ».
 */
class EnsureBackOfficeAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && Role::allows(auth()->user()->role, self::routeName($request))) {
            return $next($request);
        }

        // Les appels AJAX/API attendent un code HTTP, pas une redirection vers une page HTML.
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }

    private static function routeName(Request $request): ?string
    {
        $name = $request->route()?->getName();

        return $name === 'admin.exports.download' ? 'admin.exports.'.$request->route('list') : $name;
    }
}
