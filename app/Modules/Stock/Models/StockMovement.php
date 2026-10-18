<?php

namespace App\Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Stock\Database\Factories\StockMovementFactory;

#[UseFactory(StockMovementFactory::class)]
class StockMovement extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'product_color_id',
        'quantity',
        'type',
        'reason',
        'stock_before',
        'stock_after',
    ];
    /**
     * Get the product color variant that owns the stock movement.
     */
    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Nature du mouvement, déduite du motif (correction, inventaire, vente, achat) : [ton du badge, libellé].
     *
     * @return array{0: string, 1: string}
     */
    public function kind(): array
    {
        $reason = strtolower((string) $this->reason);

        return match (true) {
            str_starts_with($reason, 'correction') => ['warning', 'Correction'],
            str_starts_with($reason, 'inventaire') => ['info', 'Stock count'],
            str_contains($reason, 'ajustement') || str_contains($reason, 'manuel') => ['info', 'Adjustment'],
            $this->type === 'in' => ['success', str_contains($reason, 'achat') || str_contains($reason, 'pur') ? 'Purchase' : 'Stock in'],
            $this->type === 'out' => ['danger', str_contains($reason, 'vente') || str_contains($reason, 'sale') ? 'Sale' : 'Stock out'],
            default => ['neutral', 'Other'],
        };
    }
}