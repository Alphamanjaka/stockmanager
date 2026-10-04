<?php

return [
    App\Providers\AppServiceProvider::class,

    // Modules métier (app/Modules/*)
    App\Modules\Catalog\CatalogServiceProvider::class,
    App\Modules\Contact\ContactServiceProvider::class,
];
