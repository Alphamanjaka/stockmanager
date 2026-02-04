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
                throw new \Exception('Stock insuffisant pour la vente.');
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
    public function getAllStockMovements(int $perPage = 15)
    {
        return StockMovement::with('product')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
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
}
