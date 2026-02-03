<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;

// Routes d'authentification (publiques)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::get('/register', [AuthController::class, 'showRegisterPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

// Redirection de la page d'accueil vers le login ou dashboard
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Logout (protégée)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Routes protégées par authentification
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Routes Back Office (contrôle accès)
    Route::middleware('ensure.back.office')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'backOffice'])->name('dashboard');
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);
        // On limite aux méthodes existantes pour éviter les erreurs
        Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('purchases', PurchaseController::class);
    });

    // Routes Front Office
    Route::middleware('ensure.front.office')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'frontOffice'])->name('dashboard');
        // On nomme le paramètre 'sale' pour être cohérent avec la route PDF et on limite les actions
        Route::resource('', SaleController::class)->parameters(['' => 'sale'])->only(['index', 'create', 'store', 'show']);
        Route::get('/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('pdf'); // Devient sales.pdf
    });
});
