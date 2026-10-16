<?php

namespace App\Modules\Sale;

use App\Core\Support\ModuleServiceProvider;

/**
 * Module Sale : ventes (back office et vendeurs) et factures PDF.
 * Une vente décrémente le stock via le module Stock.
 */
class SaleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'sale';

    protected array $menu = [
        'sale' => [
            ['label' => 'backoffice.sales', 'icon' => 'bi-cash-coin', 'route' => 'admin.sales.index', 'shortcut' => 's', 'children' => [
                ['label' => 'backoffice.menu.all_sales', 'route' => 'admin.sales.index'],
                ['label' => 'backoffice.menu.new_sale', 'route' => 'admin.sales.create'],
            ]],
        ],
    ];
}
