<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class PurchaseService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function processPurchase(int $supplierId, array $items)
    {
        return DB::transaction(function () use ($supplierId, $items) {
            $totalAmount = 0;

            // 1. Créer l'achat
            $purchase = Purchase::create([
                'reference' => 'PUR-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'supplier_id' => $supplierId,
                'total_amount' => 0, // On mettra à jour après
                'total_net' => 0, // On mettra à jour après
                'discount' => 0 // Non géré pour l'instant
            ]);

            foreach ($items as $item) {
                // Le sous-total est basé sur le prix unitaire d'achat.
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                // 2. Créer la ligne d'achat
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // 3. AUGMENTER le stock via StockService
                $this->stockService->addStock($item['product_id'], $item['quantity'], "Achat {$purchase->reference}");
            }

            $purchase->update([
                'total_amount' => $totalAmount,
                'total_net' => $totalAmount // Pas de remise gérée ici pour l'instant
            ]);
            return $purchase;
        });
    }

    /**
     * Get all purchases with pagination
     */
    public function getAllPurchases($perPage = 15)
    {
        return Purchase::latest()->paginate($perPage);
    }

    /**
     * Get single purchase by ID
     */
    public function getPurchaseById($id)
    {
        return Purchase::with('items.product', 'supplier')->findOrFail($id);
    }

    /**
     * Get purchase statistics
     */
    public function getPurchaseStatistics()
    {
        return [
            'totalSpent' => Purchase::sum('total_net'), // Montant total dépensé pour les achats.
            'totalPurchases' => Purchase::count(),
            'averagePurchaseValue' => Purchase::avg('total_net'), // Valeur moyenne des achats.
            'totalDiscounts' => Purchase::sum('discount'), // Total des remises accordées.
        ];
    }
    public function updatePurchase($data)
    {
        $purchase = Purchase::findOrFail($data['id']);
        $purchase->update($data);
        return $purchase;
    }
}
