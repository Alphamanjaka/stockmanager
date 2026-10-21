<?php

namespace App\Modules\Purchase\Models;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(PurchaseItemFactory::class)]
class PurchaseItem extends Model
{
    use HasFactory;

    /**
     * Valeur reçue d'une ligne en SQL (requête jointe à purchases) : quantité reçue × prix de la ligne,
     * remise de l'achat répartie au prorata.
     */
    public const RECEIVED_NET_SQL = 'purchase_items.received_quantity * purchase_items.unit_price'
        .' * (CASE WHEN purchases.total_amount > 0 THEN purchases.total_net / purchases.total_amount ELSE 1 END)';

    // define fillable fields
    protected $fillable = [
        'purchase_id',
        'product_color_id',
        'quantity',
        'received_quantity', // déjà livré ; augmenté par PurchaseService à chaque réception
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'received_quantity' => 'integer'];
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    /** Quantité commandée et pas encore livrée. */
    public function remainingQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->received_quantity);
    }
}
