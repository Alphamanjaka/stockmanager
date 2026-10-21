<?php

namespace App\Modules\Sale\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Avoir : retour d'articles d'une vente (ou annulation complète). Il ne modifie pas la vente :
 * il remet les articles en stock et compte en négatif dans le chiffre d'affaires le jour où il est fait.
 */
class SaleReturn extends Model
{
    /** Préfixe des numéros d'avoir, à la suite par année : AVO-2026-000001 (DocumentNumberService). */
    public const PREFIX = 'AVO';

    protected $fillable = ['reference', 'sale_id', 'user_id', 'reason', 'total', 'refund_method'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Coût des articles rendus (coûts figés à la vente), ou null si l'un n'était pas connu. */
    public function costTotal(): ?float
    {
        if ($this->items->contains(fn (SaleReturnItem $item) => $item->unit_cost === null)) {
            return null;
        }

        return round((float) $this->items->sum(fn (SaleReturnItem $item) => $item->quantity * $item->unit_cost), 2);
    }
}
