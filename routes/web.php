<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SaleAdminController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

// Routes d'authentification (publiques)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::get('/register', [AuthController::class, 'showRegisterPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});
// language settings
Route::get('lang/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'fr', 'es'])) {
        abort(400);
    }

    session(['locale' => $locale]);

    return Redirect::back();
})->name('lang.switch');

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


        Route::resource('settings', SettingController::class);
        // add prefix settings for backup routes
        Route::prefix('settings/')->name('settings.')->group(function () {
            Route::post('backup', [SettingController::class, 'runBackup'])->name('backup');
            Route::get('backup/download', [SettingController::class, 'downloadBackup'])->name('download-backup');
            Route::delete('backup/delete', [SettingController::class, 'deleteBackup'])->name('delete-backup');
            Route::post('backup/verify', [SettingController::class, 'verifyBackup'])->name('verify-backup');
        });




        // Module d'Importation Centralisé
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

        // user module
        Route::resource('users', UserController::class);
    });

    // Routes Front Office
    Route::middleware('ensure.front.office')->prefix('saler')->name('saler.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'frontOffice'])->name('dashboard');
        Route::resource('', SaleController::class)->parameters(['' => 'sale'])->only(['index', 'create', 'store', 'show']);
        Route::get('/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('pdf'); // Devient sales.pdf
    });
});