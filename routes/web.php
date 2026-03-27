<?php

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
    SalerProductController,
    SettingController,
    UserController
};
use App\Http\Controllers\ColorController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.exportPdf');
        Route::resource('categories', CategoryController::class);

        Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('settings', SettingController::class);
        Route::post('settings/backup', [SettingController::class, 'runBackup'])->name('settings.backup');
        Route::get('settings/backup/download', [SettingController::class, 'downloadBackup'])->name('settings.download-backup');
        Route::delete('settings/backup/delete', [SettingController::class, 'deleteBackup'])->name('settings.delete-backup');
        Route::post('settings/backup/verify', [SettingController::class, 'verifyBackup'])->name('settings.verify-backup');

        // Les routes spécifiques comme 'create-from-shortage' ou 'get-purchases-api' doivent être définies
        // AVANT la route ressource pour éviter que Laravel ne les interprète comme un paramètre {id}.
        Route::get('/purchases/get-purchases-api', [PurchaseController::class, 'getPurchasesApi'])->name('purchases.get-purchases-api');
        Route::get('/purchases/create-from-shortage', [PurchaseController::class, 'createFromShortage'])->name('purchases.createFromShortage');
        Route::post('/purchases/store-from-shortage', [PurchaseController::class, 'storeFromShortage'])->name('purchases.storeFromShortage');
        Route::resource('purchases', PurchaseController::class);
        Route::patch('purchases/{id}/state', [PurchaseController::class, 'updateState'])->name('purchases.updateState');
        Route::get('purchases/{id}/pdf', [PurchaseController::class, 'exportPdf'])->name('purchases.pdf');
        Route::get('/purchases/{id}/pdf/preview', [PurchaseController::class, 'previewPdf'])
            ->name('purchases.pdf.preview')
            ->middleware('auth'); // Assurez-vous que la route est protégée

        // Module d'Importation Centralisé
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

        // user module
        Route::resource('users', UserController::class);
        Route::resource('colors', ColorController::class);
    });

    // Routes Front Office
    Route::middleware('ensure.front.office')->prefix('saler')->name('saler.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'frontOffice'])->name('dashboard');
        Route::get('/products', [SalerProductController::class, 'index'])->name('products.index');
        Route::resource('', SaleController::class)->parameters(['' => 'sale'])->only(['index', 'create', 'store', 'show']);
        Route::get('/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('pdf'); // Devient sales.pdf
    });
});
