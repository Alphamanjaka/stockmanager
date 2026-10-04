<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Contact\Http\Controllers\CustomerController;
use App\Modules\Contact\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::resource('suppliers', SupplierController::class);
Route::resource('customers', CustomerController::class);
