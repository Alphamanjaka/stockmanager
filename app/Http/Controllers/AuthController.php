<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginUserRequest;
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

        // Tente de connecter l'utilisateur avec les identifiants et le rôle
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate(); // Prévient la fixation de session

            $user = Auth::user();
            if ($user->isBackOffice()) {
                return redirect()->route('admin.dashboard')->with('success', 'Connecté avec succès !');
            }

            return redirect()->route('sales.dashboard')->with('success', 'Connecté avec succès !');
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
            // Validate the request data
            // Create the user using the UserService
            $user = $this->userService->create($request->validated());
            // Log the user in
            Auth::login($user);
            // Redirect based on role
            if ($user->isBackOffice()) {
                return redirect()->route('admin.dashboard')->with('success', 'Inscription réussie !');
            }
            return redirect()->route('sales.dashboard')->with('success', 'Inscription réussie !');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
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