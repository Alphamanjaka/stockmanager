<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock(int $productId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productId, $quantity, $reason) {
            $product = Product::lockForUpdate()->findOrFail($productId); // lockForUpdate évite les conflits Docker/multi-users

            $before = $product->quantity_stock;
            $product->increment('quantity_stock', $quantity);
            $after = $product->quantity_stock;
            $this->createStockMovement([
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });
    }

    public function removeStock(int $productId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productId, $quantity, $reason) {
            $product = Product::lockForUpdate()->findOrFail($productId); // lockForUpdate évite les conflits Docker/multi-users
            // Dans une application réelle, on ajouterait une vérification de stock ici.
            if ($product->quantity_stock < $quantity) {
                throw new \Exception('Not enough stock for this product.');
            }

            $before = $product->quantity_stock;
            $product->decrement('quantity_stock', $quantity);
            $after = $product->quantity_stock;

            $this->createStockMovement([
                'product_id' => $productId,
                'quantity' => -$quantity, // Négatif pour une sortie
                'type' => 'out',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });
    }
    public function getAllStockMovements(array $filters = [], int $perPage = 15)
    {
        $query = StockMovement::with('product');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reason', 'like', "%{$filters['search']}%")
                    ->orWhereHas('product', function ($sq) use ($filters) {
                        $sq->where('name', 'like', "%{$filters['search']}%");
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)->withQueryString();
    }
    public function getStockMovementById(int $id)
    {
        return StockMovement::with('product')->findOrFail($id);
    }

    public function getStockMovementsForProduct(int $productId, int $perPage = 10)
    {
        return StockMovement::with('product')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get stock evolution data for a chart.
     * Works backwards from current stock to ensure accuracy.
     */
    public function getStockEvolutionForProduct(int $productId): array
    {
        $product = Product::findOrFail($productId);
        $movements = StockMovement::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stockLevel = $product->quantity_stock;
        $dataPoints = [];

        // Add current state as the last point
        $dataPoints[] = ['x' => now()->toIso8601String(), 'y' => $stockLevel];

        foreach ($movements as $movement) {
            // We subtract the movement's quantity because we are going back in time.
            $stockLevel -= $movement->quantity;
            $dataPoints[] = ['x' => $movement->created_at->toIso8601String(), 'y' => $stockLevel];
        }

        return array_reverse($dataPoints);
    }
    public function createStockMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    /**
     * Récupère les produits "dormants" (stock > 0 mais aucune sortie depuis X jours).
     */
    public function getDormantProducts(int $days = 60)
    {
        $cutoffDate = now()->subDays($days);

        return Product::where('quantity_stock', '>', 0)
            ->whereNotExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.product_id', 'products.id')
                    ->where('type', 'out')
                    ->where('created_at', '>=', $cutoffDate);
            })
            ->limit(20) // Limite pour ne pas surcharger le dashboard
            ->get();
    }

    public function getRotationStats(int $limit = 5)
    {
        return StockMovement::select('product_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->where('type', 'out')
            ->groupBy('product_id')
            ->orderByDesc('total_out')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcule l'évolution de la valeur totale du stock sur les X derniers jours.
     */
    public function getTotalStockValueEvolution(int $days = 30): array
    {
        // 1. Valeur actuelle du stock global
        $currentTotalValue = Product::sum(DB::raw('quantity_stock * price'));

        // 2. Récupérer les mouvements des X derniers jours
        $movements = StockMovement::with('product')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Grouper par jour
        $movementsByDay = $movements->groupBy(function ($m) {
            return $m->created_at->format('Y-m-d');
        });

        $dataPoints = [];
        $runningValue = $currentTotalValue;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $dateString = $date->format('Y-m-d');

            $dataPoints[] = [
                'date' => $date->format('d/m'),
                'value' => max(0, round($runningValue, 2))
            ];

            if (isset($movementsByDay[$dateString])) {
                foreach ($movementsByDay[$dateString] as $movement) {
                    if ($movement->product) {
                        // On inverse le mouvement pour retrouver la valeur précédente
                        // Valeur_Avant = Valeur_Apres - (Quantité_Mouvement * Prix)
                        $runningValue -= ($movement->quantity * $movement->product->price);
                    }
                }
            }
        }

        return array_reverse($dataPoints);
    }
}
