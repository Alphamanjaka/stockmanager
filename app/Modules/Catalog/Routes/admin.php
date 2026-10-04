<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ColorController;
use App\Modules\Catalog\Http\Controllers\ProductColorController;
use Illuminate\Support\Facades\Route;

// Routes AJAX spécifiques pour l'édition rapide
Route::patch('/products/main/{id}/update-details', [ProductColorController::class, 'updateDetails'])->name('products.updateDetails');
Route::patch('/product-variants/{id}/update-stock', [ProductColorController::class, 'updateStock'])->name('product-colors.updateStock');
Route::patch('/product-variants/{id}/update-price', [ProductColorController::class, 'updatePrice'])->name('product-colors.updatePrice');

// Gestion des variantes depuis la fiche produit
Route::post('/products/{productId}/variants', [ProductColorController::class, 'storeVariant'])->name('products.variants.store');
Route::put('/products/variants/{variantId}', [ProductColorController::class, 'updateVariant'])->name('products.variants.update');
Route::delete('/products/variants/{variantId}', [ProductColorController::class, 'destroyVariant'])->name('products.variants.destroy');

Route::resource('products', ProductColorController::class);
Route::get('/products/export/pdf', [ProductColorController::class, 'exportPdf'])->name('products.exportPdf');

Route::resource('categories', CategoryController::class);
Route::resource('colors', ColorController::class);
