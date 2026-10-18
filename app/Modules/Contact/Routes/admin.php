<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Contact\Http\Controllers\CustomerController;
use App\Modules\Contact\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Archivage (un élément ou une sélection : ids[]) au lieu de la suppression ; voir ArchivesRecords.
// destroy : suppression définitive d'un contact archivé qui n'a ni achat ni vente.
foreach (['suppliers' => SupplierController::class, 'customers' => CustomerController::class] as $resource => $controller) {
    Route::post("/{$resource}/archive", [$controller, 'archive'])->name("{$resource}.archive");
    Route::post("/{$resource}/restore", [$controller, 'restore'])->name("{$resource}.restore");
}

Route::resource('suppliers', SupplierController::class);
Route::resource('customers', CustomerController::class);
