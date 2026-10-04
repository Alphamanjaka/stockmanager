<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ColorController;
use App\Modules\Catalog\Http\Controllers\ProductColorController;
use Illuminate\Support\Facades\Route;

// Routes AJAX spécifiques pour l'édition rapide
Route::patch('/products/main/{id}/update-details', [ProductColorController::class, 'updateDetails'])->name('products.updateDetails');

// Gestion des variantes depuis la fiche produit
Route::post('/products/{productId}/variants', [ProductColorController::class, 'storeVariant'])->name('products.variants.store');
Route::put('/products/variants/{variantId}', [ProductColorController::class, 'updateVariant'])->name('products.variants.update');
Route::delete('/products/variants/{variantId}', [ProductColorController::class, 'destroyVariant'])->name('products.variants.destroy');

// Pas de edit/update : la fiche produit (show) édite les informations et les variantes en AJAX
Route::resource('products', ProductColorController::class)->except(['edit', 'update']);
Route::get('/products/export/pdf', [ProductColorController::class, 'exportPdf'])->name('products.exportPdf');

Route::resource('categories', CategoryController::class);
Route::resource('colors', ColorController::class)->except(['show']);
