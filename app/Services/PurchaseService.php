<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{

    // Injecter StockService pour gérer les mouvements de stock lors des achats
    public function __construct(protected StockService $stockService) {}
    public function deletePurchase(int $id)
    {
        $purchase = Purchase::findOrFail($id);

        // Si l'achat est déjà reçu, il faut d'abord retirer le stock avant de supprimer l'achat
        if ($purchase->state === 'Received') {
            foreach ($purchase->items as $item) {
                $this->stockService->removeStock(
                    $item->product_color_id,
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
                    $item->product_color_id,
                    $item->quantity,
                    "Réception Achat #{$purchase->reference}"
                );
            }
            $purchase->update(['state' => 'Received']);
        });
    }

    // Traite un achat : création de l'achat, des lignes d'achat et mise à jour du stock
    /**
     * Traite un achat : création de l'achat, des lignes d'achat.
     * Le stock n'est pas augmenté ici mais lors du passage au statut "Reçu".
     *
     * @param int|null $supplierId
     * @param array $items
     * @return Purchase
     */
    public function processPurchase(?int $supplierId, array $items)
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
                    'product_color_id' => $item['product_color_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // 3. Le stock est maintenant augmenté uniquement lors du passage au statut "Reçu"
                // $this->stockService->addStock($item['product_color_id'], $item['quantity'], "Achat {$purchase->reference}");
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
     * @param integer $id
     * @return Purchase
     */
    public function getPurchaseById(int $id)
    {
        return Purchase::with('items.productColor.product', 'items.productColor.color', 'supplier')->findOrFail($id);
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
            'totalSpent' => Purchase::where('state', 'Received')->sum('total_net'), // Montant total dépensé pour les achats.
            'totalPurchases' => Purchase::where('state', 'Received')->count(),
            'averagePurchaseValue' => Purchase::where('state', 'Received')->avg('total_net'), // Valeur moyenne des achats.
            'totalDiscounts' => Purchase::where('state', 'Received')->sum('discount'), // Total des remises accordées.
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
                    'product_color_id' => $item['product_color_id'],
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
     * Crée des commandes d'achat groupées par fournisseur à partir d'une liste de produits en rupture.
     *
     * @param array $itemsToOrder
     * @return array Liste des achats créés
     */
    public function     createPurchasesFromShortage(array $itemsToOrder): array
    {
        return DB::transaction(function () use ($itemsToOrder) {
            $itemsBySupplier = collect($itemsToOrder)->groupBy('supplier_id');
            $createdPurchases = [];

            foreach ($itemsBySupplier as $supplierId => $items) {
                // Si le groupement par 'supplier_id' donne une clé vide (null/empty), on passe null
                $actualSupplierId = ($supplierId === "" || $supplierId === null) ? null : (int) $supplierId;
                $createdPurchases[] = $this->processPurchase($actualSupplierId, $items->toArray());
            }

            return $createdPurchases;
        });
    }

    /**
     * Regroupe les produits en rupture de stock par leur dernier fournisseur connu.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShortageProductsGroupedBySupplier()
    {
        // --- ANCIEN CODE COMMENTÉ ---
        /*
        $productColorService = app(ProductColorService::class);
        $shortageProducts = $productColorService->getShortageProducts();
        ... (logique Query Builder)
        */

        // --- NOUVELLE LOGIQUE SQL BRUT ---
        $sql = "
            SELECT
                pc.id, pc.stock, pc.alert_stock, pc.price,
                p.name as product_name,
                c.name as color_name,
                lp.unit_price as last_unit_price,
                lp.supplier_id as last_supplier_id,
                lp.supplier_name as last_supplier_name
            FROM product_colors pc
            INNER JOIN products p ON pc.product_id = p.id
            INNER JOIN colors c ON pc.color_id = c.id
            LEFT JOIN (
                SELECT pi.product_color_id, pi.unit_price, s.id as supplier_id, s.name as supplier_name
                FROM purchase_items pi
                JOIN purchases pur ON pi.purchase_id = pur.id
                JOIN suppliers s ON pur.supplier_id = s.id
                WHERE pi.created_at = (
                    SELECT MAX(created_at)
                    FROM purchase_items
                    WHERE product_color_id = pi.product_color_id
                )
            ) lp ON pc.id = lp.product_color_id
            WHERE pc.stock <= pc.alert_stock AND pc.alert_stock > 0
        ";

        $results = DB::select($sql);

        if (empty($results)) {
            return collect();
        }

        $productsWithSupplier = collect($results)->map(function ($item) {
            // Formatage des noms pour la vue (objet simulé)
            $product = new \stdClass();
            foreach ($item as $key => $value) {
                $product->$key = $value;
            }

            // Données de repli si aucun historique fournisseur
            if (!$product->last_supplier_id) {
                $product->last_supplier_name = 'No Supplier Assigned';
                $product->last_unit_price = $product->price * 0.75;
            }

            // Calcul de la suggestion de quantité (Indépendant du fournisseur)
            $deficit = $product->alert_stock - $product->stock;
            $product->suggested_quantity = (int) ceil($deficit + ($product->alert_stock * 0.5));
            if ($product->suggested_quantity <= 0) {
                $product->suggested_quantity = $product->alert_stock > 0 ? $product->alert_stock : 10;
            }

            return $product;
        });

        // Regroupement final (Gardé pour la compatibilité de la vue, mais inclut les 'null')
        return $productsWithSupplier->groupBy('last_supplier_id')
            ->map(function ($products, $supplierId) {
                return [
                    'supplier_name' => $products->first()->last_supplier_name,
                    'supplier_id' => $supplierId,
                    'products' => $products
                ];
            });
    }

    /**
     * Transforme le panier brut de la session en collection d'objets complets.
     *
     * @param array $cart [product_color_id => ['quantity' => x, 'unit_price' => y]]
     * @return \Illuminate\Support\Collection
     */
    public function getCartDetails(array $cart)
    {
        if (empty($cart)) return collect();

        $productIds = array_keys($cart);
        $products = \App\Models\ProductColor::with(['product', 'color'])->find($productIds);

        return $products->map(function ($product) use ($cart) {
            $item = $cart[$product->id];
            return (object) [
                'product' => $product,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity'] * $item['unit_price']
            ];
        });
    }
}
