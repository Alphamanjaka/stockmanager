<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
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

    // Get stock movements for a product; optional query param ?variant_id= to filter by variant
    public function getMovements(int $productId, Request $request)
    {
        $variantId = $request->query('variant_id');

        if (!empty($variantId)) {
            // movements for a specific variant (product_color_id)
            $movements = StockMovement::with(['productColor.color'])
                ->where('product_color_id', (int) $variantId)
                ->orderBy('created_at', 'desc')
                ->paginate(50);
        } else {
            // movements for all variants of the product
            $movements = $this->stockManagementService->getStockMovementsForProduct($productId, 50);
        }

        return StockMovementResource::collection($movements);
    }

    public function getStockEvolution(int $productId)
    {
        $evolution = $this->stockManagementService->getStockEvolutionForProduct($productId);
        return response()->json([
            'success' => true,
            'data' => $evolution
        ]);
    }

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
