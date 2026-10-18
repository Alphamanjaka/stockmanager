<?php

namespace App\Modules\Stock;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Stock\Console\Commands\MonitorStockDrop;
use App\Modules\Stock\Exports\StockMovementsExport;

/**
 * Module Stock : mouvements de stock (entrées/sorties), alertes et valorisation.
 * Toute variation de stock passe par StockService, qui l'historise : achat reçu (entrée), vente (sortie),
 * inventaire physique, et correction manuelle en secours d'une erreur de saisie.
 */
class StockServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'stock';

    protected array $commands = [
        MonitorStockDrop::class,
    ];

    protected array $exports = [
        'movements' => StockMovementsExport::class,
    ];

    protected array $menu = [
        'stock' => [
            ['label' => 'backoffice.movements', 'icon' => 'bi-arrow-left-right', 'route' => 'admin.movements.index', 'shortcut' => 'm', 'children' => [
                ['label' => 'backoffice.menu.movement_history', 'route' => 'admin.movements.index'],
                ['label' => 'backoffice.menu.new_movement', 'route' => 'admin.movements.create'],
            ]],
        ],
    ];
}
