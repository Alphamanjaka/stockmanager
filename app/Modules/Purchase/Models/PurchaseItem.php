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
