<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ColorController;
use App\Modules\Catalog\Http\Controllers\ProductColorController;
use Illuminate\Support\Facades\Route;

// Routes AJAX spécifiques pour l'édition rapide
Route::patch('/products/main/{id}/update-details', [ProductColorController::class, 'updateDetails'])->name('products.updateDetails');

// Gestion des variantes depuis la fiche produit
Route::post('/products/{productId}/variants', [ProductColorController::class, 'storeVariant'])->name('products.variants.store');
Route::put('/products/variants/{variantId}', [ProductColorController::class, 'updateVariant'])->name('products.variants.update');
Route::put('/products/{product}/options', [ProductColorController::class, 'updateOptions'])->whereNumber('product')->name('products.options.update');
Route::delete('/products/variants/{variantId}', [ProductColorController::class, 'destroyVariant'])->name('products.variants.destroy');

// Archivage (un élément ou une sélection : ids[]) au lieu de la suppression ; voir ArchivesRecords.
// Déclarées avant les ressources : /products/archive ne doit pas être lu comme /products/{product}.
foreach (['products' => ProductColorController::class, 'categories' => CategoryController::class,
    'colors' => ColorController::class, 'brands' => BrandController::class] as $resource => $controller) {
    Route::post("/{$resource}/archive", [$controller, 'archive'])->name("{$resource}.archive");
    Route::post("/{$resource}/restore", [$controller, 'restore'])->name("{$resource}.restore");
}

// Pas de edit/update : la fiche produit (show) édite les informations et les variantes en AJAX
// destroy : suppression définitive d'un produit archivé et jamais utilisé
Route::resource('products', ProductColorController::class)->except(['edit', 'update']);

Route::resource('categories', CategoryController::class);
Route::resource('colors', ColorController::class)->except(['show']);
Route::resource('brands', BrandController::class)->except(['show']);
