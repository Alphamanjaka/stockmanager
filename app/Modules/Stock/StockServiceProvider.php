<?php

namespace App\Modules\Stock;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Stock\Console\Commands\MonitorStockDrop;

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
}
