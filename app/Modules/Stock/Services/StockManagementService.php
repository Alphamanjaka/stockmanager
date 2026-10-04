<?php

namespace App\Modules\Stock\Services;

use App\Modules\Catalog\Models\ProductColor;

class StockManagementService extends StockService
{
    public function getStockPerColor(int $productId)
    {
        return ProductColor::with('color')
            ->where('product_id', $productId)
            ->get();
    }
}
