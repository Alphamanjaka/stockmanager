<?php

namespace App\Modules\Inventory;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Inventory\Exports\InventoriesExport;

/**
 * Module Inventory : inventaires physiques (comptage du stock réel).
 * La validation d'un inventaire ajuste le stock via le module Stock (un mouvement par écart).
 */
class InventoryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'inventory';

    protected array $exports = [
        'inventories' => InventoriesExport::class,
    ];

    protected array $menu = [
        'stock' => [
            ['label' => 'backoffice.stock_counts', 'icon' => 'bi-clipboard-check', 'route' => 'admin.inventories.index', 'children' => [
                ['label' => 'backoffice.menu.all_stock_counts', 'route' => 'admin.inventories.index'],
                ['label' => 'backoffice.menu.new_stock_count', 'route' => 'admin.inventories.create'],
            ]],
        ],
    ];
}
