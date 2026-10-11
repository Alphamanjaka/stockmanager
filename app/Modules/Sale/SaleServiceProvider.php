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
}
