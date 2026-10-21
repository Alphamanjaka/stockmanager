<?php

namespace App\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Core\Http\Requests\LoginUserRequest;
use App\Http\Controllers\Controller;

/**
 * Connexion et déconnexion. Il n'y a pas d'inscription publique :
 * les comptes sont créés par un administrateur (admin/users).
 */
class AuthController extends Controller
{
    /**
     * Show the login page
     */
    public function showLoginPage()
    {
        return view('core::auth.login');
    }

    /**
     * Handle user login: the space (back office or front office) follows the account's role
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        // 5 échecs par minute au plus pour un même email depuis une même adresse IP
        $request->ensureIsNotRateLimited();

        // Un compte archivé ne se connecte plus (il garde son historique de ventes et d'inventaires)
        if (Auth::attemptWhen($credentials, fn ($user) => !$user->isArchived(), $request->boolean('remember'))) {
            $request->clearRateLimiter();
            $request->session()->regenerate(); // Prévient la fixation de session

            $user = Auth::user();

            $dashboardRoute = $user->isBackOffice()
                ? 'admin.dashboard'
                : 'saler.dashboard';

            // Redirige vers la page que l'utilisateur voulait visiter, ou vers son tableau de bord.
            return redirect()->intended(route($dashboardRoute))
                ->with('success', 'Connecté avec succès !');
        }

        $request->hitRateLimiter();

        // Identifiants justes mais compte archivé : message explicite (le mot de passe a été vérifié)
        $message = Auth::validate($credentials)
            ? 'Votre compte a été désactivé : contactez un administrateur.'
            : 'Les identifiants fournis sont incorrects.';

        return back()->withErrors(['email' => $message])->onlyInput('email');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Vous avez été déconnecté.');
    }
}
