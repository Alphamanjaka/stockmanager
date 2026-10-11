<?php

// AJAX du back office (préfixe /api, protégé par ModuleServiceProvider)

use App\Modules\Catalog\Http\Controllers\Api\CategoryControllerAPI;
use App\Modules\Catalog\Http\Controllers\Api\ColorControllerAPI;
use App\Modules\Catalog\Http\Controllers\Api\ProductColorControllerAPI;
use Illuminate\Support\Facades\Route;

// Fiche produit (AJAX)
Route::get('products/{productId}/variants', [ProductColorControllerAPI::class, 'getVariants']);

Route::apiResource('categories', CategoryControllerAPI::class);
Route::apiResource('colors', ColorControllerAPI::class)->only(['index', 'store']);
