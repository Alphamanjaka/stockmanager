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
        'stock_at_validation',
    ];

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
     * Écart de la ligne : par rapport au stock réel à la validation une fois validée,
     * sinon par rapport à la quantité attendue (photo à la création).
     */
    public function gap(): ?int
    {
        if (!$this->isCounted()) {
            return null;
        }

        return $this->counted_quantity - ($this->stock_at_validation ?? $this->expected_quantity);
    }
}
