<?php

// Front office (préfixe /saler, noms saler.*, protégé par ModuleServiceProvider)

use App\Modules\Catalog\Http\Controllers\SalerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [SalerProductController::class, 'index'])->name('products.index');
