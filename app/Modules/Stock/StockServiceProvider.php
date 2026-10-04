<?php

namespace App\Modules\Stock;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Catalog\Events\VariantStockDeclared;
use App\Modules\Stock\Console\Commands\MonitorStockDrop;
use App\Modules\Stock\Listeners\RecordDeclaredStock;

/**
 * Module Stock : mouvements de stock (entrées/sorties), alertes et valorisation.
 * Toute variation de stock doit passer par StockService pour être historisée.
 */
class StockServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'stock';

    protected array $commands = [
        MonitorStockDrop::class,
    ];

    protected array $listen = [
        // Stock saisi dans le catalogue (création, fiche produit, import) → mouvement de stock
        VariantStockDeclared::class => [RecordDeclaredStock::class],
    ];
}
