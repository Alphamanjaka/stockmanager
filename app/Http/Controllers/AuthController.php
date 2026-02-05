<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        try {
            $user = $this->userService->login($request->validated());

            Auth::login($user);

            if ($user->isBackOffice()) {
                return redirect()->route('admin.dashboard')->with('success', 'Connecté avec succès !');
            }

            return redirect()->route('sales.dashboard')->with('success', 'Connecté avec succès !');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Handle user registration with role selection
     */
    public function register(RegisterUserRequest $request)
    {
        try {
            // Validate the request data
            $validated = $request->validated();
            // Create the user using the UserService
            $user = $this->userService->register($validated);
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
