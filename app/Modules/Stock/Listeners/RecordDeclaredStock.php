<?php

namespace App\Modules\Stock\Listeners;

use App\Modules\Catalog\Events\VariantStockDeclared;
use App\Modules\Stock\Services\StockService;

/**
 * Applique le stock déclaré par le catalogue en passant par un mouvement de stock.
 */
class RecordDeclaredStock
{
    public function __construct(private StockService $stockService) {}

    public function handle(VariantStockDeclared $event): void
    {
        $this->stockService->adjustTo($event->variant->id, $event->quantity, $event->reason);

        $event->variant->refresh();
    }
}
