<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Catalog\Support\VariantOptions;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use App\Modules\Stock\Services\StockService;

class PurchaseService
{
    /** Actions du bon de commande et statut obtenu (le brouillon n'en change pas). */
    public const ACTIONS = ['draft' => null, 'ordered' => 'Ordered', 'received' => 'Received'];

    // Injecter StockService pour gérer les mouvements de stock lors des achats
    public function __construct(protected StockService $stockService) {}
    public function deletePurchase(int $id)
    {
        // Tout ou rien : si le retrait du stock échoue pour une ligne (articles déjà vendus),
        // aucun retrait n'est conservé et l'achat n'est pas supprimé.
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->lockForUpdate()->findOrFail($id);

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
        });
    }


    // Make a purchase as received: update stock and purchase state
    public function markAsReceived(Purchase $purchase): Purchase
    {
        return $this->changeState($purchase, 'Received');
    }

    /**
     * Change le statut d'un achat.
     * "Received" est un statut final : le stock a déjà été incrémenté, et revenir en arrière
     * permettrait de réceptionner la même commande plusieurs fois.
     *
     * @throws \Exception si l'achat est déjà réceptionné
     */
    public function changeState(Purchase $purchase, string $state): Purchase
    {
        return DB::transaction(function () use ($purchase, $state) {
            // Verrou sur la ligne : deux requêtes simultanées (double clic) ne peuvent pas réceptionner deux fois.
            $locked = Purchase::with('items')->lockForUpdate()->findOrFail($purchase->id);

            if ($locked->state === 'Received') {
                throw new \Exception("L'achat #{$locked->reference} a déjà été réceptionné : son statut ne peut plus être modifié.");
            }

            if ($state === 'Received') {
                foreach ($locked->items as $item) {
                    //We increase stock for each item in the purchase
                    $this->stockService->addStock(
                        $item->product_color_id,
                        $item->quantity,
                        "Réception Achat #{$locked->reference}"
                    );
                }
            }

            $locked->update(['state' => $state]);

            return $locked;
        });
    }

    // Traite un achat : création de l'achat, des lignes d'achat et mise à jour du stock
    /**
     * Traite un achat : création de l'achat, des lignes d'achat.
     * Le stock n'est pas augmenté ici mais lors du passage au statut "Reçu".
     *
     * @param int|null $supplierId
     * @param array $items  [['product_color_id', 'quantity', 'unit_price'], ...]
     * @param array $details  facultatif : discount, supplier_reference, notes
     * @return Purchase
     */
    public function processPurchase(?int $supplierId, array $items, array $details = [])
    {
        return DB::transaction(function () use ($supplierId, $items, $details) {
            $totalAmount = 0;

            // 1. Créer l'achat
            $purchase = Purchase::create([
                'reference' => 'PUR-' . now()->format('YmdHis') . '-' . rand(100, 789546),
                'supplier_id' => $supplierId,
                'supplier_reference' => $details['supplier_reference'] ?? null,
                'notes' => $details['notes'] ?? null,
                'total_amount' => 0, // On mettra à jour après
                'total_net' => 0, // On mettra à jour après
                'discount' => 0,
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

            $discount = $this->discount($details['discount'] ?? 0, $totalAmount);
            $purchase->update([
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'total_net' => $totalAmount - $discount,
            ]);
            return $purchase;
        });
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
    /**
     * Modifie un brouillon : fournisseur, lignes (remplacées) et, s'ils sont fournis,
     * remise, référence fournisseur et notes.
     *
     * @throws \Exception si l'achat n'est plus en brouillon
     */
    public function updatePurchase(int $id, array $data): Purchase
    {
        return DB::transaction(function () use ($id, $data) {
            $purchase = $this->getPurchaseById($id);

            if ($purchase->state !== 'Draft') {
                throw new \Exception("Impossible de modifier une commande qui n'est plus en brouillon (Statut actuel : {$purchase->state}).");
            }

            // Mise à jour de l'en-tête (les champs absents sont conservés)
            $purchase->update(['supplier_id' => $data['supplier_id']]
                + array_intersect_key($data, array_flip(['supplier_reference', 'notes'])));

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
            $discount = $this->discount($data['discount'] ?? $purchase->discount, $totalAmount);
            $purchase->update([
                'total_amount' => $totalAmount,
                'discount'     => $discount,
                'total_net'    => $totalAmount - $discount,
            ]);

            return $purchase;
        });
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
     * Effectue une recherche légère sur les achats pour la fonctionnalité de recherche globale.
     *
     * @param string $query La chaîne de recherche.
     * @param int $limit Le nombre maximum de résultats.
     * @return \Illuminate\Support\Collection
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        return Purchase::with('supplier')
            ->whereLike('reference', "%{$query}%")
            ->orWhereHas('supplier', fn($q) => $q->whereLike('name', "%{$query}%"))
            ->limit($limit)
            ->get()
            ->map(fn(Purchase $purchase) => [
                'type' => 'purchase',
                'id' => $purchase->id,
                'name' => "Achat #{$purchase->reference} (" . ($purchase->supplier->name ?? 'Sans fournisseur') . ")",
                'url' => route('admin.purchases.show', $purchase->id),
            ]);
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
                        $query->whereLike('reference', '%' . $filter['value'] . '%');
                    }
                    if ($filter['field'] === 'supplier.name') {
                        $query->whereHas('supplier', function ($q) use ($filter) {
                            $q->whereLike('name', '%' . $filter['value'] . '%');
                        });
                    }
                }
            }
        }

        // Tri dynamique
        // Seules les colonnes de la table purchases sont triables : un champ inconnu (ex. "supplier.name")
        // provoquait une erreur SQL.
        $sortable = ['reference', 'total_amount', 'discount', 'total_net', 'state', 'created_at'];
        if (!empty($params['sort']) && is_array($params['sort'])) {
            foreach ($params['sort'] as $s) {
                if (in_array($s['field'] ?? null, $sortable, true)) {
                    $query->orderBy($s['field'], ($s['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
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
                p.options as product_options,
                pc.option_values, pc.sku,
                b.name as brand_name,
                lp.unit_price as last_unit_price,
                lp.supplier_id as last_supplier_id,
                lp.supplier_name as last_supplier_name
            FROM product_colors pc
            INNER JOIN products p ON pc.product_id = p.id
            LEFT JOIN brands b ON pc.brand_id = b.id
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
              AND p.archived_at IS NULL
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

            $product->label = VariantOptions::label(
                $product->product_name,
                json_decode($product->option_values ?? '[]', true) ?? [],
                $product->brand_name,
                json_decode($product->product_options ?? 'null', true)
            );

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
     * Enregistre un bon de commande (création, ou modification d'un brouillon) puis applique l'action :
     * 'draft' reste en brouillon, 'ordered' passe en commandé, 'received' réceptionne (entrée en stock).
     * Tout ou rien : si la réception échoue, l'achat n'est pas enregistré non plus.
     *
     * @param array $data supplier_id, products[], discount, supplier_reference, notes
     */
    public function savePurchaseOrder(?int $purchaseId, array $data, string $action = 'draft'): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $data, $action) {
            $purchase = $purchaseId
                ? $this->updatePurchase($purchaseId, $data)
                : $this->processPurchase($data['supplier_id'], $data['products'], $data);

            $state = self::ACTIONS[$action] ?? null;

            return $state ? $this->changeState($purchase, $state) : $purchase->fresh();
        });
    }

    /**
     * Dernier prix unitaire payé à un fournisseur pour chaque variante : [product_color_id => prix].
     * Sert à proposer le coût d'une ligne dans le bon de commande.
     */
    public function lastUnitPricesForSupplier(int $supplierId): array
    {
        // Lignes du plus ancien au plus récent : la dernière écrase les précédentes
        return PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.supplier_id', $supplierId)
            ->orderBy('purchase_items.created_at')
            ->orderBy('purchase_items.id')
            ->pluck('purchase_items.unit_price', 'purchase_items.product_color_id')
            ->map(fn ($price) => (float) $price)
            ->all();
    }

    /** Remise d'un achat : positive et jamais supérieure au total brut. */
    private function discount($discount, float $totalAmount): float
    {
        $discount = max(0, (float) $discount);

        if ($discount > $totalAmount) {
            throw new \Exception("La remise ne peut pas dépasser le total de l'achat.");
        }

        return $discount;
    }
}
