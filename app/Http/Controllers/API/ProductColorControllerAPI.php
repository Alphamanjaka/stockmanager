<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Services\{ProductService, CategoryService, ColorService, ProductColorService, SaleService, StockManagementService};

class ProductColorControllerAPI extends Controller
{
    //
    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService,
        protected ColorService $colorService,
        protected ProductColorService $productColorService,
        protected SaleService $saleService,
        protected StockManagementService $stockManagementService
    ) {}

    // Get stock movement for a specific product variant return using StockMovementResource
    public function getMouvemenetVariant(int $idVariant)
    {
        $movements = $this->stockManagementService->getStockMovementById($idVariant);
        return StockMovementResource::collection($movements);
    }
    public function getStockEvolution(int $idVariant)
    {
        $evolution = $this->stockManagementService->getStockEvolutionForVariant($idVariant);
        return response()->json($evolution);
    }

    public function getVariants(int $idProduct)
    {
        $variants = $this->productColorService->listByProduct($idProduct);
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