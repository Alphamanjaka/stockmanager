<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Stock\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);
