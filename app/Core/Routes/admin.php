<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Core\Http\Controllers\DailySummaryController;
use App\Core\Http\Controllers\DashboardController;
use App\Core\Http\Controllers\ExportController;
use App\Core\Http\Controllers\ImportController;
use App\Core\Http\Controllers\NotificationController;
use App\Core\Http\Controllers\SettingController;
use App\Core\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'backOffice'])->name('dashboard');
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartDataApi'])->name('dashboard.chart-data');

// Résumé quotidien : aperçu d'un jour, réglages de l'envoi par e-mail, e-mail d'essai
Route::prefix('daily-summary')->name('daily-summary.')->controller(DailySummaryController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/preview', 'preview')->name('preview');
    Route::put('/settings', 'updateSettings')->name('settings');
    Route::post('/test', 'sendTest')->name('test');
});

// Cloche : ouvrir une notification (marquée comme lue) ou tout marquer comme lu
Route::get('/notifications/{notification}', [NotificationController::class, 'read'])->whereUuid('notification')->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

// Paramètres
Route::resource('settings', SettingController::class)->only(['index', 'update']);

// Export des listes en PDF ou Excel (les classes d'export appartiennent à chaque module : ExportRegistry)
Route::get('/exports/{list}/{format}', ExportController::class)
    ->where(['list' => '[a-z][a-z-]*', 'format' => 'pdf|xlsx'])->name('exports.download');

// Import centralisé (les classes d'import appartiennent à chaque module)
Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

// Utilisateurs : archiver un compte le désactive (ids[] : un compte ou une sélection) ;
// destroy : suppression définitive d'un compte archivé sans historique
Route::post('/users/archive', [UserController::class, 'archive'])->name('users.archive');
Route::post('/users/restore', [UserController::class, 'restore'])->name('users.restore');
Route::resource('users', UserController::class)->except(['show']);
