<?php

use App\Http\Controllers\API\CategoryControllerAPI;
use App\Http\Controllers\API\ColorControllerAPI;
use App\Http\Controllers\API\ProductColorControllerAPI;
use Illuminate\Support\Facades\Route;


// Routes spécifiques pour le Dashboard Produit (AJAX) et pour les mouvements de stock
Route::get('products/{productId}/variants', [ProductColorControllerAPI::class, 'getVariants']);
Route::get('products/{productId}/stock-evolution', [ProductColorControllerAPI::class, 'getStockEvolution']);
Route::get('products/{productId}/movements', [ProductColorControllerAPI::class, 'getMovements']);
Route::apiResource('categories', CategoryControllerAPI::class);
Route::apiResource('colors', ColorControllerAPI::class);
