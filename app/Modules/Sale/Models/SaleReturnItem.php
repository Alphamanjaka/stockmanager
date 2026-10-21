<?php

namespace App\Modules\Sale\Models;

use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = ['sale_return_id', 'sale_item_id', 'product_color_id', 'quantity', 'unit_price', 'unit_cost', 'subtotal'];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }
}
