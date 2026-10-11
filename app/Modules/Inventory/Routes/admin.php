<?php

// Back office (préfixe /admin, noms admin.*, protégé par ModuleServiceProvider)

use App\Modules\Inventory\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

// PUT : le bouton de validation soumet le formulaire de comptage (qui envoie _method=PUT)
Route::put('/inventories/{inventory}/validate', [InventoryController::class, 'validateInventory'])->name('inventories.validate');
Route::resource('inventories', InventoryController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
