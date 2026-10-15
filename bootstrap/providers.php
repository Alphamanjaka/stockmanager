<?php

use App\Core\CoreServiceProvider;
use App\Modules\Backup\BackupServiceProvider;
use App\Modules\Catalog\CatalogServiceProvider;
use App\Modules\Contact\ContactServiceProvider;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\Purchase\PurchaseServiceProvider;
use App\Modules\Sale\SaleServiceProvider;
use App\Modules\Stock\StockServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,

    // Modules métier (app/Modules/*)
    CatalogServiceProvider::class,
    ContactServiceProvider::class,
    StockServiceProvider::class,
    PurchaseServiceProvider::class,
    SaleServiceProvider::class,
    InventoryServiceProvider::class,
    BackupServiceProvider::class,
];
