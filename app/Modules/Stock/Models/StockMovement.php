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
}