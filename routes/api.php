<?php

use App\Http\Controllers\API\CategoryControllerAPI;
use App\Http\Controllers\API\ColorControllerAPI;
use App\Http\Controllers\API\ProductColorControllerAPI;
use Illuminate\Support\Facades\Route;

// Registers standard RESTful endpoints for products
Route::apiResource('/categories', CategoryControllerAPI::class);
Route::apiResource('/colors', ColorControllerAPI::class);
Route::apiResource('products-variants', ProductColorControllerAPI::class);

// Routes spécifiques pour le Dashboard Produit (AJAX)
Route::get('products/{product}/variants', [ProductColorControllerAPI::class, 'getVariants']);
Route::get('products/{product}/stock-evolution', [ProductColorControllerAPI::class, 'getStockEvolution']);
Route::get('products/{product}/movements', [ProductColorControllerAPI::class, 'getMovements']);
