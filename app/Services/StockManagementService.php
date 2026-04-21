<?php

namespace App\Services;

use App\Models\ProductColor;

class StockManagementService extends StockService
{
    /**
     * Flux : Produit A + Couleur Rouge = Stock 10
     */
    public function assignStock(int $productId, int $colorId, int $stock)
    {
        // On utilise updateOrCreate pour éviter les doublons dans la pivot
        return ProductColor::updateOrCreate(
            ['product_id' => $productId, 'color_id' => $colorId],
            ['stock' => $stock]
        );
    }

    public function getStockPerColor(int $productId)
    {
        return ProductColor::with('color')
            ->where('product_id', $productId)
            ->get();
    }

}
