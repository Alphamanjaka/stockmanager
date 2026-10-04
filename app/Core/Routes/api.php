<?php

// AJAX du back office (préfixe /api, protégé par ModuleServiceProvider)

use App\Core\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::get('global-search', [GlobalSearchController::class, 'search'])->name('global.search');
