<?php

// Points d'entrée publics de l'application.
// Les pages métier sont déclarées par chaque module (app/Core/Routes, app/Modules/*/Routes).

use App\Core\Http\Controllers\AuthController;
use App\Core\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Connexion (publique). Pas d'inscription publique : les comptes sont créés par un administrateur.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Redirection de la page d'accueil vers le login ou dashboard
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Logout (protégée)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Redirige vers le tableau de bord du rôle (admin.dashboard ou saler.dashboard)
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
