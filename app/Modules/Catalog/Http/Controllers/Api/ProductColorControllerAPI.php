<?php

namespace App\Modules\Catalog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductColorService;

class ProductColorControllerAPI extends Controller
{
    public function __construct(protected ProductColorService $productColorService) {}

    public function getVariants(int $productId)
    {
        $variants = $this->productColorService->listByProduct($productId);
        return response()->json([
            'variants' => $variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'color' => $variant->color->name,
                    'stock' => $variant->stock,
                    'alert_stock' => $variant->alert_stock,
                ];
            })
        ]);
    }
}
