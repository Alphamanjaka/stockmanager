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
            $product = Product::findOrFail($productId);
            $product->increment('quantity_stock', $quantity);

            StockMovement::create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => $reason,
            ]);
        });
    }

    public function removeStock(int $productId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productId, $quantity, $reason) {
            $product = Product::findOrFail($productId);

            // Dans une application réelle, on ajouterait une vérification de stock ici.
            // if ($product->quantity_stock < $quantity) {
            //     throw new \Exception('Stock insuffisant pour la vente.');
            // }

            $product->decrement('quantity_stock', $quantity);

            StockMovement::create([
                'product_id' => $productId,
                'quantity' => -$quantity, // Négatif pour une sortie
                'type' => 'out',
                'reason' => $reason,
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
}