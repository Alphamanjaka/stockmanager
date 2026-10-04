<?php

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le catalogue déclare le stock attendu d'une variante (création, fiche produit, import).
 *
 * Catalog n'écrit jamais la colonne stock lui-même : le module Stock écoute cet événement
 * et enregistre le mouvement correspondant à l'écart, pour que tout changement soit historisé.
 */
class VariantStockDeclared
{
    use Dispatchable;

    public function __construct(
        public ProductColor $variant,
        public int $quantity,
        public string $reason,
    ) {}
}
