<?php

namespace App\Modules\Catalog\Support;

/**
 * Prix de vente proposé à partir du prix d'achat et de la marge par défaut des Paramètres
 * (Stock & products › default_margin_percent), en taux de marge sur le coût :
 * prix de vente = prix d'achat × (1 + marge / 100). Ex. coût 10 000, marge 30 % → 13 000.
 *
 * Le même calcul est fait dans le navigateur (resources/js/price-suggestion.js) : le prix se remplit
 * pendant la saisie du coût, et reste modifiable. Ici, il ne sert qu'à compléter un prix laissé vide.
 */
class Pricing
{
    /** Marge par défaut en %, ou null si elle n'est pas configurée. */
    public static function defaultMargin(): ?float
    {
        $margin = settings('default_margin_percent');

        return is_numeric($margin) && (float) $margin >= 0 ? (float) $margin : null;
    }

    /** Prix de vente proposé pour ce coût, ou null sans coût ou sans marge configurée. */
    public static function suggestedPrice(mixed $cost): ?float
    {
        $margin = self::defaultMargin();

        if ($margin === null || !is_numeric($cost) || (float) $cost <= 0) {
            return null;
        }

        return round((float) $cost * (100 + $margin) / 100, 2);
    }
}
