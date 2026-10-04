<?php

namespace App\Modules\Inventory;

use App\Core\Support\ModuleServiceProvider;

/**
 * Module Inventory : inventaires physiques (comptage du stock réel).
 * La validation d'un inventaire ajuste le stock via le module Stock (un mouvement par écart).
 */
class InventoryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'inventory';
}
