<?php

use App\Http\Controllers\{ProductController, SaleController, AuthController, DashboardController, CategoryController, ImportController, StockMovementController, SupplierController, PurchaseController, SalerProductController, SettingController, UserController};
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ProductColorController;
use App\Http\Controllers\SaleAdminController;
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
        // sales routes for back office
        Route::resource('sales', SaleAdminController::class);
        // export pdf for sale details in back office
        Route::get('/sales/{id}/pdf', [SaleAdminController::class, 'exportPdf'])->name('sales.pdf');
        // Routes AJAX spécifiques pour l'édition rapide
        Route::patch('/products/main/{id}/update-details', [ProductColorController::class, 'updateDetails'])->name('products.updateDetails');
        Route::patch('/product-variants/{id}/update-stock', [ProductColorController::class, 'updateStock'])->name('product-colors.updateStock');
        Route::patch('/product-variants/{id}/update-price', [ProductColorController::class, 'updatePrice'])->name('product-colors.updatePrice');
        // New routes for variant management on product show page
        Route::post('/products/{productId}/variants', [ProductColorController::class, 'storeVariant'])->name('products.variants.store');
        Route::put('/products/variants/{variantId}', [ProductColorController::class, 'updateVariant'])->name('products.variants.update');
        Route::delete('/products/variants/{variantId}', [ProductColorController::class, 'destroyVariant'])->name('products.variants.destroy');

        Route::resource('products', ProductColorController::class);
        Route::get('/products/export/pdf', [ProductColorController::class, 'exportPdf'])->name('products.exportPdf');
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


        Route::get('/purchases/{id}/pdf/preview', [PurchaseController::class, 'previewPdf'])
            ->name('purchases.pdf.preview')
            ->middleware('auth'); // Assurez-vous que la route est protégée
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Route::get('/{id}/pdf/preview', [PurchaseController::class, 'previewPdf'])->name('pdf.preview');
            Route::get('/get-purchases-api', [PurchaseController::class, 'getPurchasesApi'])->name('get-purchases-api');
            Route::get('/create-from-shortage', [PurchaseController::class, 'createFromShortage'])->name('createFromShortage');
            Route::post('/store-from-shortage', [PurchaseController::class, 'storeFromShortage'])->name('storeFromShortage');
            // route post cart, delete cart, clear cart
            Route::post('/cart/add', [PurchaseController::class, 'addToCart'])->name('cart.add');
            Route::delete('/cart/remove/{id}', [PurchaseController::class, 'removeFromCart'])->name('cart.remove');
            Route::post('/cart/clear', [PurchaseController::class, 'clearCart'])->name('cart.clear');
            Route::patch('/{id}/state', [PurchaseController::class, 'updateState'])->name('updateState');
            Route::get('/{id}/pdf', [PurchaseController::class, 'exportPdf'])->name('pdf');
        });
        Route::resource('purchases', PurchaseController::class);



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
