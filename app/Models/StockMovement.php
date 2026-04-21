<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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