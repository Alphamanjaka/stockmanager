<?php

// AJAX du back office (préfixe /api, protégé par ModuleServiceProvider)

use App\Modules\Stock\Http\Controllers\Api\StockMovementControllerAPI;
use Illuminate\Support\Facades\Route;

// Fiche produit : historique et évolution du stock
Route::get('products/{productId}/stock-evolution', [StockMovementControllerAPI::class, 'getStockEvolution']);
Route::get('products/{productId}/movements', [StockMovementControllerAPI::class, 'getMovements']);
