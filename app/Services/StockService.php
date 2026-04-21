<?php

namespace App\Services;

use App\Models\ProductColor;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock(int $productColorId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productColorId, $quantity, $reason) {
            $variant = ProductColor::lockForUpdate()->findOrFail($productColorId);

            $before = $variant->stock;
            $variant->increment('stock', $quantity);
            $after = $variant->stock;

            $this->createStockMovement([
                'product_color_id' => $productColorId,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });
    }

    public function removeStock(int $productColorId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productColorId, $quantity, $reason) {
            $variant = ProductColor::with('product', 'color')->lockForUpdate()->findOrFail($productColorId);

            if ($variant->stock < $quantity) {
                throw new \Exception("Stock insuffisant pour la variante {$variant->product->name} ({$variant->color->name}).");
            }

            $before = $variant->stock;
            $variant->decrement('stock', $quantity);
            $after = $variant->stock;

            $this->createStockMovement([
                'product_color_id' => $productColorId,
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
        $query = StockMovement::with(['productColor.product', 'productColor.color']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reason', 'like', "%{$filters['search']}%")
                    ->orWhereHas('productColor.product', function ($sq) use ($filters) {
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
        return StockMovement::with(['productColor.product', 'productColor.color'])->findOrFail($id);
    }

    public function getStockMovementsForProduct(int $productId, int $perPage = 10)
    {
        return StockMovement::whereHas('productColor', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
            ->with(['productColor.color'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get stock evolution data for a specific variant (ProductColor).
     */
    public function getStockEvolutionForVariant(int $productColorId): array
    {
        $variant = ProductColor::findOrFail($productColorId);
        $movements = StockMovement::where('product_color_id', $productColorId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stockLevel = $variant->stock;
        $dataPoints = [];

        $dataPoints[] = ['x' => now()->toIso8601String(), 'y' => $stockLevel];

        foreach ($movements as $movement) {
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
     * Récupère les variantes "dormantes" (stock > 0 mais aucune sortie depuis X jours).
     */
    public function getDormantProducts(int $days = 60)
    {
        $cutoffDate = now()->subDays($days);

        return ProductColor::with(['product', 'color'])
            ->where('stock', '>', 0)
            ->whereNotExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.product_color_id', 'product_colors.id')
                    ->where('type', 'out')
                    ->where('created_at', '>=', $cutoffDate);
            })
            ->limit(20)
            ->get();
    }

    public function getRotationStats(int $limit = 5)
    {
        return StockMovement::select('product_color_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->where('type', 'out')
            ->groupBy('product_color_id')
            ->orderByDesc('total_out')
            ->with(['productColor.product', 'productColor.color'])
            ->limit($limit)
            ->get();
    }

    /**
     * Calcule l'évolution de la valeur totale du stock sur les X derniers jours.
     */
    public function getTotalStockValueEvolution(int $days = 30): array
    {
        $currentTotalValue = ProductColor::join('products', 'product_colors.product_id', '=', 'products.id')
            ->sum(DB::raw('product_colors.stock * products.price'));

        $movements = StockMovement::with('productColor.product')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->orderBy('created_at', 'desc')
            ->get();

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
                    if ($movement->productColor && $movement->productColor->product) {
                        $runningValue -= ($movement->quantity * $movement->productColor->product->price);
                    }
                }
            }
        }

        return array_reverse($dataPoints);
    }
}