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
}
