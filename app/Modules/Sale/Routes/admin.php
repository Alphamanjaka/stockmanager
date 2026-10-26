<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Sale\Http\Controllers\SaleAdminController;
use App\Modules\Sale\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

// Analyse ABC des produits (avant la ressource : /sales/abc n'est pas une vente)
Route::get('/sales/abc', [SaleAdminController::class, 'abc'])->name('sales.abc');

// Avoirs : retour d'articles ou annulation d'une vente (back office seulement)
Route::get('/sales/{sale}/returns/create', [SaleReturnController::class, 'create'])->whereNumber('sale')->name('sales.returns.create');
Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store'])->whereNumber('sale')->name('sales.returns.store');
Route::get('/sale-returns/{saleReturn}', [SaleReturnController::class, 'show'])->whereNumber('saleReturn')->name('sale-returns.show');

Route::resource('sales', SaleAdminController::class)->only(['index', 'create', 'store', 'show']);
Route::get('/sales/{id}/pdf', [SaleAdminController::class, 'exportPdf'])->name('sales.pdf');
Route::get('/sales/{id}/receipt', [SaleAdminController::class, 'receipt'])->whereNumber('id')->name('sales.receipt');
