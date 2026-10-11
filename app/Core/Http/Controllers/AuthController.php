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
     * Show the login page with role selection
     */
    public function showLoginPage()
    {
        return view('core::auth.login');
    }

    /**
     * Handle user login with role selection
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate(); // Prévient la fixation de session

            $user = Auth::user();

            $dashboardRoute = $user->isBackOffice()
                ? 'admin.dashboard'
                : 'saler.dashboard';

            // Redirige vers la page que l'utilisateur voulait visiter, ou vers son tableau de bord.
            return redirect()->intended(route($dashboardRoute))
                ->with('success', 'Connecté avec succès !');
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects ou vous n\'avez pas accès à ce profil.',
        ])->onlyInput('email', 'role');
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
