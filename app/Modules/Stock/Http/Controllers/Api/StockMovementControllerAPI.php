<?php

namespace App\Modules\Stock\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Stock\Http\Resources\StockMovementResource;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Services\StockManagementService;

/**
 * Données de stock affichées sur la fiche produit (AJAX).
 */
class StockMovementControllerAPI extends Controller
{
    public function __construct(protected StockManagementService $stockManagementService) {}

    // Get stock movements for a product; optional query param ?variant_id= to filter by variant
    public function getMovements(int $productId, Request $request)
    {
        $variantId = $request->query('variant_id');

        if (!empty($variantId)) {
            // movements for a specific variant (product_color_id)
            $movements = StockMovement::with(['productColor.color', 'productColor.product', 'productColor.brand'])
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
}
