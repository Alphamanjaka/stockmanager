<?php

namespace App\Modules\Stock\Support;

/**
 * Origine d'un mouvement de stock : le document qui l'a provoqué (type et identifiant).
 * Le type est un libellé court et stable, pas un nom de classe : le module Stock ne dépend pas
 * des modules qui l'utilisent (Achats, Ventes, Inventaire). Le motif reste un texte pour l'humain.
 */
final class MovementSource
{
    public const PURCHASE = 'purchase';

    public const SALE = 'sale';

    public const INVENTORY = 'inventory';

    public const CORRECTION = 'correction';

    public const SALE_RETURN = 'sale_return';

    public function __construct(public readonly string $type, public readonly ?int $id = null) {}

    /** Réception d'un achat (entrée), ou annulation de cette réception (sortie, achat supprimé : sans id). */
    public static function purchase(?int $purchaseId): self
    {
        return new self(self::PURCHASE, $purchaseId);
    }

    public static function sale(int $saleId): self
    {
        return new self(self::SALE, $saleId);
    }

    /** Retour d'articles vendus (avoir) : remise en stock. */
    public static function saleReturn(int $saleReturnId): self
    {
        return new self(self::SALE_RETURN, $saleReturnId);
    }

    public static function inventory(int $inventoryId): self
    {
        return new self(self::INVENTORY, $inventoryId);
    }

    /** Correction manuelle : pas de document, le motif saisi l'explique. */
    public static function correction(): self
    {
        return new self(self::CORRECTION);
    }
}
