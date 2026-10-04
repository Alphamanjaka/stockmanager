<?php

// Front office (préfixe /saler, noms saler.*, protégé par ModuleServiceProvider)

use App\Modules\Sale\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

// {sale} est limité aux nombres : /saler/products ou /saler/dashboard (autres modules)
// ne doivent jamais être capturés par la fiche vente, quel que soit l'ordre de chargement.
Route::resource('', SaleController::class)
    ->parameters(['' => 'sale'])
    ->only(['index', 'create', 'store', 'show'])
    ->where(['sale' => '[0-9]+']);
Route::get('/{sale}/pdf', [SaleController::class, 'exportPdf'])->whereNumber('sale')->name('pdf');
