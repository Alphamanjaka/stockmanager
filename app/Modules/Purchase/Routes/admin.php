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
    // Bon de commande : derniers prix payés au fournisseur (coût proposé pour chaque ligne)
    Route::get('/supplier-prices/{supplier}', [PurchaseController::class, 'supplierPrices'])
        ->whereNumber('supplier')->name('supplier-prices');
    Route::patch('/{id}/state', [PurchaseController::class, 'updateState'])->name('updateState');
    Route::get('/{id}/pdf', [PurchaseController::class, 'exportPdf'])->name('pdf');
});
Route::resource('purchases', PurchaseController::class);
