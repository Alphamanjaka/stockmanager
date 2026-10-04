<?php

use App\Http\Controllers\API\CategoryControllerAPI;
use App\Http\Controllers\API\ColorControllerAPI;
use App\Http\Controllers\API\ProductColorControllerAPI;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalSearchController;

// Ces endpoints sont appelés en AJAX depuis les pages du back office (session navigateur).
// 'web' fournit la session et la protection CSRF, 'auth' + 'ensure.back.office' réservent l'accès aux administrateurs.
Route::middleware(['web', 'auth', 'ensure.back.office'])->group(function () {
    // Routes spécifiques pour le Dashboard Produit (AJAX) et pour les mouvements de stock
    Route::get('products/{productId}/variants', [ProductColorControllerAPI::class, 'getVariants']);
    Route::get('products/{productId}/stock-evolution', [ProductColorControllerAPI::class, 'getStockEvolution']);
    Route::get('products/{productId}/movements', [ProductColorControllerAPI::class, 'getMovements']);
    Route::apiResource('categories', CategoryControllerAPI::class);
    Route::apiResource('colors', ColorControllerAPI::class)->only(['index', 'store']);
    Route::get('global-search', [GlobalSearchController::class, 'search'])->name('global.search');
});
