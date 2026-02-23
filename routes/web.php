<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProductController,
    SaleController,
    AuthController,
    DashboardController,
    CategoryController,
    ImportController,
    StockMovementController,
    SupplierController,
    PurchaseController,
    SettingsController
};

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
        Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartDataApi'])->name('dashboard.chart-data');
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);

        Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('settings', SettingsController::class);

        // Cette route doit être définie AVANT la ressource pour éviter que "get-purchases-api" ne soit interprété comme un ID
        Route::get('/purchases/get-purchases-api', [PurchaseController::class, 'getPurchasesApi'])->name('purchases.get-purchases-api');
        Route::resource('purchases', PurchaseController::class);
        Route::patch('purchases/{id}/state', [PurchaseController::class, 'updateState'])->name('purchases.updateState');
        Route::get('purchases/{id}/pdf', [PurchaseController::class, 'exportPdf'])->name('purchases.pdf');

        // Module d'Importation Centralisé
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

        // user module
        Route::resource('users', UserController::class);
    });

    // Routes Front Office
    Route::middleware('ensure.front.office')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'frontOffice'])->name('dashboard');
        Route::resource('', SaleController::class)->parameters(['' => 'sale'])->only(['index', 'create', 'store', 'show']);
        Route::get('/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('pdf'); // Devient sales.pdf
    });
});
