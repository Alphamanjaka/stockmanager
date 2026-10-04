<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalSearchController;

// Ces endpoints sont appelés en AJAX depuis les pages du back office (session navigateur).
// 'web' fournit la session et la protection CSRF, 'auth' + 'ensure.back.office' réservent l'accès aux administrateurs.
Route::middleware(['web', 'auth', 'ensure.back.office'])->group(function () {
    Route::get('global-search', [GlobalSearchController::class, 'search'])->name('global.search');
});
