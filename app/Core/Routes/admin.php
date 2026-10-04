<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Core\Http\Controllers\DashboardController;
use App\Core\Http\Controllers\ImportController;
use App\Core\Http\Controllers\SettingController;
use App\Core\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'backOffice'])->name('dashboard');
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartDataApi'])->name('dashboard.chart-data');

// Paramètres et sauvegardes
Route::resource('settings', SettingController::class);
Route::prefix('settings/')->name('settings.')->group(function () {
    Route::post('backup', [SettingController::class, 'runBackup'])->name('backup');
    Route::get('backup/download', [SettingController::class, 'downloadBackup'])->name('download-backup');
    Route::delete('backup/delete', [SettingController::class, 'deleteBackup'])->name('delete-backup');
    Route::post('backup/verify', [SettingController::class, 'verifyBackup'])->name('verify-backup');
});

// Import centralisé (les classes d'import appartiennent à chaque module)
Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

// Utilisateurs
Route::resource('users', UserController::class);
