<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Database\Eloquent\Model;

class InventoryLine extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_color_id',
        'expected_quantity',
        'counted_quantity',
        'stock_at_count',
        'stock_at_validation',
    ];

    protected function casts(): array
    {
        return [
            'expected_quantity' => 'integer',
            'counted_quantity' => 'integer',
            'stock_at_count' => 'integer',
            'stock_at_validation' => 'integer',
        ];
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }

    /**
     * Écart de la ligne : compté − stock au moment du comptage. C'est l'écart que la validation applique
     * au stock courant. Lignes validées avant l'enregistrement du stock au comptage : par rapport au stock
     * à la validation (le stock était alors ramené à la quantité comptée).
     */
    public function gap(): ?int
    {
        if (! $this->isCounted()) {
            return null;
        }

        return $this->counted_quantity - ($this->stock_at_count ?? $this->stock_at_validation ?? $this->expected_quantity);
    }

    /** Stock après validation : stock à la validation + écart, jamais négatif. */
    public function stockAfterValidation(): ?int
    {
        if (! $this->isCounted() || $this->stock_at_validation === null) {
            return null;
        }

        return max(0, $this->stock_at_validation + $this->gap());
    }
}
