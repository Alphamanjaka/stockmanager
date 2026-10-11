<?php

// Front office (préfixe /saler, noms saler.*, protégé par ModuleServiceProvider)

use App\Core\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'frontOffice'])->name('dashboard');
