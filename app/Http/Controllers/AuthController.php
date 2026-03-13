<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\RegisterUserRequest;
use App\Services\UserService;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Show the login page with role selection
     */
    public function showLoginPage()
    {
        return view('auth.login');
    }

    /**
     * Show the registration page with role selection
     */
    public function showRegisterPage()
    {
        return view('auth.register');
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
                : 'sales.dashboard';

            // Redirige vers la page que l'utilisateur voulait visiter, ou vers son tableau de bord.
            return redirect()->intended(route($dashboardRoute))
                ->with('success', 'Connecté avec succès !');
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects ou vous n\'avez pas accès à ce profil.',
        ])->onlyInput('email', 'role');
    }

    /**
     * Handle user registration with role selection
     */
    public function register(RegisterUserRequest $request)
    {
        try {
            $user = $this->userService->create($request->validated());

            Auth::login($user);

            // Régénère la session pour la sécurité
            $request->session()->regenerate();

            $dashboardRoute = $user->isBackOffice()
                ? 'admin.dashboard'
                : 'sales.dashboard';

            return redirect()->route($dashboardRoute)->with('success', 'Inscription réussie !');
        } catch (\Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Une erreur est survenue lors de l\'inscription.'])->withInput();
        }
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
