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
}
