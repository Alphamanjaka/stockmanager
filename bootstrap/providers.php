<?php

return [
    App\Providers\AppServiceProvider::class,

    // Modules métier (app/Modules/*)
    App\Modules\Catalog\CatalogServiceProvider::class,
    App\Modules\Contact\ContactServiceProvider::class,
    App\Modules\Stock\StockServiceProvider::class,
    App\Modules\Purchase\PurchaseServiceProvider::class,
    App\Modules\Sale\SaleServiceProvider::class,
];
