<?php

namespace App\Modules\Purchase;

use App\Core\Support\ModuleServiceProvider;

/**
 * Module Purchase : commandes fournisseurs, panier d'achat, réapprovisionnement depuis les ruptures.
 * La réception d'un achat alimente le stock via le module Stock.
 */
class PurchaseServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'purchase';

    protected array $menu = [
        'purchase' => [
            ['label' => 'backoffice.purchases', 'icon' => 'bi-receipt', 'route' => 'admin.purchases.index', 'shortcut' => 'a', 'children' => [
                ['label' => 'backoffice.menu.all_purchases', 'route' => 'admin.purchases.index'],
                ['label' => 'backoffice.menu.new_purchase', 'route' => 'admin.purchases.create'],
                ['label' => 'backoffice.menu.restock', 'route' => 'admin.purchases.createFromShortage'],
            ]],
        ],
    ];
}
