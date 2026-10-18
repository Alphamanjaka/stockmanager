<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Core\Http\Controllers\DashboardController;
use App\Core\Http\Controllers\ImportController;
use App\Core\Http\Controllers\SettingController;
use App\Core\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'backOffice'])->name('dashboard');
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartDataApi'])->name('dashboard.chart-data');

// Paramètres
Route::resource('settings', SettingController::class)->only(['index', 'update']);

// Import centralisé (les classes d'import appartiennent à chaque module)
Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

// Utilisateurs : archiver un compte le désactive (ids[] : un compte ou une sélection) ;
// destroy : suppression définitive d'un compte archivé sans historique
Route::post('/users/archive', [UserController::class, 'archive'])->name('users.archive');
Route::post('/users/restore', [UserController::class, 'restore'])->name('users.restore');
Route::resource('users', UserController::class)->except(['show']);
