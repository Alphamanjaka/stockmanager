<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected $stockService;

    // Injecter StockService pour gérer les mouvements de stock lors des achats
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }


    // Make a purchase as received: update stock and purchase state
    public function markAsReceived(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                //We increase stock for each item in the purchase
                $this->stockService->addStock(
                    $item->product_id,
                    $item->quantity,
                    "Réception Achat #{$purchase->reference}"
                );
            }
            $purchase->update(['state' => 'Received']);
        });
    }

    // Traite un achat : création de l'achat, des lignes d'achat et mise à jour du stock
    public function processPurchase(int $supplierId, array $items)
    {
        return DB::transaction(function () use ($supplierId, $items) {
            $totalAmount = 0;

            // 1. Créer l'achat
            $purchase = Purchase::create([
                'reference' => 'PUR-' . now()->format('YmdHis') . '-' . rand(100, 789546),
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

                // 3. Le stock est maintenant augmenté uniquement lors du passage au statut "Reçu"
                // $this->stockService->addStock($item['product_id'], $item['quantity'], "Achat {$purchase->reference}");
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
    public function getAllPurchases(int $perPage = 15, array $filters = [])
    {
        $query = Purchase::with('supplier')->latest();

        if (!empty($filters['search'])) {
            $query->where('reference', 'like', "%{$filters['search']}%")
                ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$filters['search']}%"));
        }
        return $this->applyFilters($query, $filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Get single purchase by ID
     */
    public function getPurchaseById($id)
    {
        return Purchase::with('items.product', 'supplier')->findOrFail($id);
    }

    /**
     * Récupère les achats paginés pour un fournisseur spécifique.
     *
     * @param integer $supplierId
     * @param integer $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPurchasesForSupplier(int $supplierId, int $perPage = 10)
    {
        return Purchase::where('supplier_id', $supplierId)->latest()->paginate($perPage);
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
    // Update purchase details (like changing supplier or reference)
    public function updatePurchase(int $id, array $data): Purchase
    {
        $purchase = $this->getPurchaseById($id);
        $purchase->update($data);
        return $purchase;
    }
    // Method to apply filters to the purchase query
    public function applyFilters($query, $filters)
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }
        return $query;
    }
}
