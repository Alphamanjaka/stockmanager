<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Purchase\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

// Les routes spécifiques ('create-from-shortage', 'get-purchases-api'...) doivent être déclarées
// AVANT la route ressource pour ne pas être interprétées comme un paramètre {purchase}.
Route::prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/{id}/pdf/preview', [PurchaseController::class, 'previewPdf'])->name('pdf.preview');
    Route::get('/get-purchases-api', [PurchaseController::class, 'getPurchasesApi'])->name('get-purchases-api');
    Route::get('/create-from-shortage', [PurchaseController::class, 'createFromShortage'])->name('createFromShortage');
    Route::post('/store-from-shortage', [PurchaseController::class, 'storeFromShortage'])->name('storeFromShortage');
    // Panier d'achat (session)
    Route::post('/cart/add', [PurchaseController::class, 'addToCart'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [PurchaseController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/cart/clear', [PurchaseController::class, 'clearCart'])->name('cart.clear');
    Route::patch('/{id}/state', [PurchaseController::class, 'updateState'])->name('updateState');
    Route::get('/{id}/pdf', [PurchaseController::class, 'exportPdf'])->name('pdf');
});
Route::resource('purchases', PurchaseController::class);
