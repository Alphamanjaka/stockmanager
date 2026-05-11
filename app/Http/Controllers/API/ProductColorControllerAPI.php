<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ProductColorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use App\Models\StockMovement; // Added for getMovements

class ProductColorControllerAPI extends Controller
{
    public function __construct(
        protected ProductColorService $productColorService,
        protected StockService $stockService
    ) {}

    /**
     * Récupère toutes les variantes d'un produit spécifique.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariants(int $productId)
    {
        $variants = $this->productColorService->listByProduct($productId);
        return response()->json([
            'data' => $variants,
            'count' => $variants->count()
        ]);
    }

    /**
     * Données pour le graphique d'évolution du stock (cumul des variantes).
     * NOTE: L'implémentation actuelle utilise la logique existante pour une variante (la première trouvée).
     * Pour un cumul réel de toutes les variantes d'un produit, une logique d'agrégation plus complexe
     * serait nécessaire dans StockService pour sommer les mouvements historiques de toutes les variantes.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStockEvolution(int $productId)
    {
        $variants = $this->productColorService->listByProduct($productId);
        $data = [];

        if ($variants->isNotEmpty()) {
            // On récupère l'évolution de la première variante par défaut ou on pourrait agréger
            // Pour l'instant, on utilise la logique existante pour une variante
            $data = $this->stockService->getStockEvolutionForVariant($variants->first()->id);
        }

        return response()->json([
            'labels' => array_column($data, 'x'), // Dates
            'data' => array_column($data, 'y'),   // Valeurs de stock
        ]);
    }

    /**
     * Historique des mouvements avec filtre optionnel par variante.
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMovements(Request $request, int $productId)
    {
        $variantId = $request->query('variant_id');

        $query = StockMovement::query()
            ->whereHas('productColor', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with(['productColor.color'])
            ->when($variantId, fn($q) => $q->where('product_color_id', $variantId))
            ->latest();

        return response()->json($query->paginate(10));
    }
}
