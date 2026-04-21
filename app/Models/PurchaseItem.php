<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;
    // define fillable fields
    protected $fillable = [
        'purchase_id',
        'product_color_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];
    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }
}
