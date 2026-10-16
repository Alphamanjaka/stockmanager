<?php

namespace App\Modules\Catalog;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Catalog\Console\Commands\MigrateVariants;

/**
 * Module Catalog : produits, variantes, catégories, couleurs et marques.
 * Variantes flexibles : voir docs/migration-variantes.md.
 */
class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'catalog';

    protected array $commands = [
        MigrateVariants::class,
    ];

    protected array $menu = [
        'catalog' => [
            ['label' => 'backoffice.products', 'icon' => 'bi-box', 'route' => 'admin.products.index', 'shortcut' => 'p', 'children' => [
                ['label' => 'backoffice.menu.all_products', 'route' => 'admin.products.index'],
                ['label' => 'backoffice.menu.new_product', 'route' => 'admin.products.create'],
            ]],
            ['label' => 'backoffice.categories', 'icon' => 'bi-diagram-3', 'route' => 'admin.categories.index', 'shortcut' => 'c'],
            ['label' => 'backoffice.brands', 'icon' => 'bi-tag', 'route' => 'admin.brands.index'],
            ['label' => 'backoffice.colors', 'icon' => 'bi-palette', 'route' => 'admin.colors.index', 'shortcut' => 'o'],
        ],
    ];
}
