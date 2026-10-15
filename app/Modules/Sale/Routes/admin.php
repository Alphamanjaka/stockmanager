<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Sale\Http\Controllers\SaleAdminController;
use Illuminate\Support\Facades\Route;

Route::resource('sales', SaleAdminController::class)->only(['index', 'create', 'store', 'show']);
Route::get('/sales/{id}/pdf', [SaleAdminController::class, 'exportPdf'])->name('sales.pdf');
Route::get('/sales/{id}/receipt', [SaleAdminController::class, 'receipt'])->whereNumber('id')->name('sales.receipt');
