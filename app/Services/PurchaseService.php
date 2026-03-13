<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;

class PurchaseService
{
    protected $stockService;

    // Injecter StockService pour gérer les mouvements de stock lors des achats
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }
    public function deletePurchase(int $id)
    {
        $purchase = Purchase::findOrFail($id);

        // Si l'achat est déjà reçu, il faut d'abord retirer le stock avant de supprimer l'achat
        if ($purchase->state === 'Received') {
            foreach ($purchase->items as $item) {
                $this->stockService->removeStock(
                    $item->product_id,
                    $item->quantity,
                    "Annulation Réception Achat #{$purchase->reference}"
                );
            }
        }

        $purchase->delete();
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
            'totalSpent' => Purchase::where('state', 'Received')->orWhere('state', 'Paid')->sum('total_net'), // Montant total dépensé pour les achats.
            'totalPurchases' => Purchase::where('state', 'Received')->orWhere('state', 'Paid')->count(),
            'averagePurchaseValue' => Purchase::where('state', 'Received')->orWhere('state', 'Paid')->avg('total_net'), // Valeur moyenne des achats.
            'totalDiscounts' => Purchase::where('state', 'Received')->orWhere('state', 'Paid')->sum('discount'), // Total des remises accordées.
        ];
    }
    // Update purchase details (like changing supplier or reference)
    public function updatePurchase(int $id, array $data): Purchase
    {
        return DB::transaction(function () use ($id, $data) {
            $purchase = $this->getPurchaseById($id);

            if ($purchase->state !== 'Draft') {
                throw new \Exception("Impossible de modifier une commande qui n'est plus en brouillon (Statut actuel : {$purchase->state}).");
            }

            // Mise à jour du fournisseur
            $purchase->update([
                'supplier_id' => $data['supplier_id']
            ]);

            // Mise à jour des lignes d'achat (On supprime et on recrée pour simplifier)
            $purchase->items()->delete();

            $totalAmount = 0;
            foreach ($data['products'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $subtotal,
                ]);
            }

            // Recalcul des totaux
            $purchase->update([
                'total_amount' => $totalAmount,
                'total_net'    => $totalAmount - $purchase->discount,
            ]);

            return $purchase;
        });
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

    /**
     * Get the count of purchases for each state, plus a total.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPurchaseStateCounts()
    {
        $counts = Purchase::query()
            ->select('state', DB::raw('count(*) as count'))
            ->groupBy('state')
            ->get()
            ->pluck('count', 'state');

        // Add the total count for the "All" tab
        $counts['All'] = Purchase::count();

        return $counts;
    }

    /**
     * Récupère les achats pour l'API (Tabulator) avec tri dynamique et recherche.
     */
    public function getPurchasesForApi(array $params = [], int $perPage = 15)
    {
        $query = Purchase::with('supplier');

        // Traitement des filtres de Tabulator (onglets et recherche par colonne)
        if (!empty($params['filter']) && is_array($params['filter'])) {
            foreach ($params['filter'] as $filter) {
                if (isset($filter['field']) && isset($filter['value']) && $filter['value'] !== null) {
                    if ($filter['field'] === 'state') {
                        $query->where('state', '=', $filter['value']);
                    }
                    if ($filter['field'] === 'reference') {
                        $query->where('reference', 'like', '%' . $filter['value'] . '%');
                    }
                    if ($filter['field'] === 'supplier.name') {
                        $query->whereHas('supplier', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter['value'] . '%');
                        });
                    }
                }
            }
        }

        // Tri dynamique
        if (!empty($params['sort']) && is_array($params['sort'])) {
            foreach ($params['sort'] as $s) {
                if (isset($s['field']) && isset($s['dir'])) {
                    $query->orderBy($s['field'], $s['dir']);
                }
            }
        } else {
            $query->latest(); // Tri par défaut
        }

        return $query->paginate($perPage);
    }

    /**
     * Regroupe les produits en rupture de stock par leur dernier fournisseur connu.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShortageProductsGroupedBySupplier()
    {
        $productService = app(ProductService::class);
        $shortageProducts = $productService->getShortageProducts();

        if ($shortageProducts->isEmpty()) {
            return collect();
        }

        $productIds = $shortageProducts->pluck('id');

        // Requête optimisée pour trouver le dernier achat pour chaque produit
        $subQuery = DB::table('purchase_items')
            ->select('product_id', DB::raw('MAX(created_at) as last_purchase_date'))
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id');

        $lastPurchases = DB::table('purchase_items as pi')
            ->joinSub($subQuery, 'latest_pi', function ($join) {
                $join->on('pi.product_id', '=', 'latest_pi.product_id')
                    ->on('pi.created_at', '=', 'latest_pi.last_purchase_date');
            })
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->select('pi.product_id', 'pi.unit_price', 's.id as supplier_id', 's.name as supplier_name')
            ->get()
            ->keyBy('product_id');

        // Associer les informations de fournisseur à chaque produit
        $productsWithSupplier = $shortageProducts->map(function ($product) use ($lastPurchases) {
            if (isset($lastPurchases[$product->id])) {
                $purchaseInfo = $lastPurchases[$product->id];
                $product->last_supplier_id = $purchaseInfo->supplier_id;
                $product->last_supplier_name = $purchaseInfo->supplier_name;
                $product->last_unit_price = $purchaseInfo->unit_price;
            } else {
                $product->last_supplier_id = null;
                $product->last_supplier_name = 'Aucun historique d\'achat';
                $product->last_unit_price = $product->price * 0.75; // Prix de repli
            }

            // Suggérer une quantité à commander
            $deficit = $product->alert_stock - $product->quantity_stock;
            $product->suggested_quantity = (int) ceil($deficit + ($product->alert_stock * 0.5));
            if ($product->suggested_quantity <= 0) {
                $product->suggested_quantity = $product->alert_stock > 0 ? $product->alert_stock : 10;
            }

            return $product;
        });

        // Grouper par fournisseur pour la vue
        return $productsWithSupplier->groupBy('last_supplier_id')
            ->map(function ($products, $supplierId) {
                return [
                    'supplier_name' => $products->first()->last_supplier_name,
                    'supplier_id' => $supplierId,
                    'products' => $products
                ];
            });
    }
}
