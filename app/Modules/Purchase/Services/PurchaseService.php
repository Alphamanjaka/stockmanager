<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Catalog\Support\VariantOptions;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

            // Ce qui a déjà été livré (réception complète ou partielle) sort du stock avant la suppression
            foreach ($purchase->items->where('received_quantity', '>', 0) as $item) {
                // Source sans identifiant : l'achat est supprimé juste après (le motif garde sa référence)
                $this->stockService->removeStock(
                    $item->product_color_id,
                    $item->received_quantity,
                    "Annulation Réception Achat #{$purchase->reference}",
                    MovementSource::purchase(null)
                );
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
     * Change le statut d'un achat. « Received » réceptionne tout ce qui reste à livrer.
     * « Received » est un statut final : le stock a déjà été incrémenté, et revenir en arrière
     * permettrait de réceptionner la même commande plusieurs fois. Un achat en partie reçu ne revient
     * pas non plus en brouillon ni en commande : une partie de son stock est déjà entrée.
     *
     * @throws \Exception si l'achat est déjà réceptionné, ou en partie reçu pour un retour en arrière
     */
    public function changeState(Purchase $purchase, string $state): Purchase
    {
        return DB::transaction(function () use ($purchase, $state) {
            // Verrou sur la ligne : deux requêtes simultanées (double clic) ne peuvent pas réceptionner deux fois.
            $locked = $this->lockOpenPurchase($purchase);

            if ($state === Purchase::RECEIVED) {
                return $this->receiveLines($locked, $locked->items->mapWithKeys(
                    fn (PurchaseItem $item) => [$item->id => $item->remainingQuantity()]
                )->all(), requireQuantity: false);
            }

            if ($locked->state === Purchase::PARTIALLY_RECEIVED) {
                throw new \Exception("L'achat #{$locked->reference} est déjà en partie reçu : il ne peut plus revenir en brouillon ni en commande.");
            }

            $locked->update(['state' => $state]);

            return $locked;
        });
    }

    /**
     * Enregistre une livraison, complète ou partielle : [id de ligne => quantité livrée].
     * Une ligne absente ou à 0 n'est pas livrée cette fois. L'achat devient « partiellement reçu »
     * tant qu'il reste des articles à livrer, puis « reçu » quand tout est arrivé.
     *
     * @param  array<int, int|string|null>  $quantities
     *
     * @throws \Exception quantité supérieure au reste à livrer, livraison vide, achat déjà reçu
     */
    public function receive(Purchase $purchase, array $quantities): Purchase
    {
        return DB::transaction(fn () => $this->receiveLines($this->lockOpenPurchase($purchase), $quantities));
    }

    /**
     * Solde une commande en partie reçue : le fournisseur ne livrera pas le reste. L'achat devient « reçu »
     * et le reste n'est plus attendu (il ne compte plus dans les quantités commandées) ; les quantités
     * reçues restent celles livrées.
     *
     * @throws \Exception si l'achat n'est pas en partie reçu
     */
    public function closeRemaining(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $locked = $this->lockOpenPurchase($purchase);

            if ($locked->state !== Purchase::PARTIALLY_RECEIVED) {
                throw new \Exception("Seule une commande en partie reçue peut être soldée : réceptionnez d'abord ce qui a été livré.");
            }

            $locked->update(['state' => Purchase::RECEIVED]);

            return $locked;
        });
    }

    /** Achat verrouillé jusqu'à la fin de la transaction, refusé s'il est déjà entièrement reçu. */
    private function lockOpenPurchase(Purchase $purchase): Purchase
    {
        $locked = Purchase::with('items.productColor')->lockForUpdate()->findOrFail($purchase->id);

        if ($locked->state === Purchase::RECEIVED) {
            throw new \Exception("L'achat #{$locked->reference} a déjà été réceptionné : son statut ne peut plus être modifié.");
        }

        return $locked;
    }

    /**
     * Entrée en stock des quantités livrées (achat verrouillé), puis statut selon ce qui reste à livrer.
     * $requireQuantity : une livraison sans aucun article est refusée (réception de tout le reste : non).
     */
    private function receiveLines(Purchase $purchase, array $quantities, bool $requireQuantity = true): Purchase
    {
        // Remise de l'achat répartie sur les lignes au prorata : coût réellement payé par article
        $netRatio = $purchase->netRatio();
        $received = 0;

        foreach ($purchase->items as $item) {
            $quantity = (int) ($quantities[$item->id] ?? 0);
            if ($quantity <= 0) {
                continue;
            }
            if ($quantity > $item->remainingQuantity()) {
                throw new \Exception("{$item->productColor?->label} : {$quantity} reçu(s) pour {$item->remainingQuantity()} restant(s) à livrer.");
            }

            // Entrée en stock ; le coût de la variante devient son coût moyen pondéré (CUMP)
            $this->stockService->addStock(
                $item->product_color_id,
                $quantity,
                "Réception Achat #{$purchase->reference}",
                (float) $item->unit_price * $netRatio,
                MovementSource::purchase($purchase->id)
            );
            $item->increment('received_quantity', $quantity);
            $received += $quantity;
        }

        if ($received === 0 && $requireQuantity) {
            throw new \Exception('Indiquez la quantité livrée d\'au moins une ligne.');
        }

        $complete = $purchase->items->every(fn (PurchaseItem $item) => $item->remainingQuantity() === 0);
        $purchase->update(['state' => $complete ? Purchase::RECEIVED : Purchase::PARTIALLY_RECEIVED]);

        return $purchase;
    }

    // Traite un achat : création de l'achat, des lignes d'achat et mise à jour du stock
    /**
     * Traite un achat : création de l'achat, des lignes d'achat.
     * Le stock n'est pas augmenté ici mais lors du passage au statut "Reçu".
     *
     * @param  array  $items  [['product_color_id', 'quantity', 'unit_price'], ...]
     * @param  array  $details  facultatif : discount, supplier_reference, notes
     * @return Purchase
     */
    public function processPurchase(?int $supplierId, array $items, array $details = [])
    {
        return DB::transaction(function () use ($supplierId, $items, $details) {
            $totalAmount = 0;

            // 1. Créer l'achat
            $purchase = Purchase::create([
                'reference' => 'PUR-'.now()->format('YmdHis').'-'.rand(100, 789546),
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
     *
     * @return Purchase
     */
    public function getPurchaseById(int $id)
    {
        return Purchase::with('items.productColor.product', 'items.productColor.color', 'items.productColor.brand', 'supplier')->findOrFail($id);
    }

    /**
     * Récupère les achats paginés pour un fournisseur spécifique.
     *
     * @return LengthAwarePaginator
     */
    public function getPurchasesForSupplier(int $supplierId, int $perPage = 10)
    {
        return Purchase::where('supplier_id', $supplierId)->latest()->paginate($perPage);
    }

    /**
     * Statistiques des achats qui ont fait entrer du stock (partiellement ou entièrement reçus),
     * sur ce qui a été livré : une commande soldée avant la fin ne compte que pour ses articles reçus.
     */
    public function getPurchaseStatistics()
    {
        $totals = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchases.state', Purchase::WITH_RECEPTIONS)
            ->selectRaw('COUNT(DISTINCT purchases.id) as purchases')
            ->selectRaw('SUM(purchase_items.received_quantity * purchase_items.unit_price) as gross')
            ->selectRaw('SUM('.PurchaseItem::RECEIVED_NET_SQL.') as net')
            ->toBase()
            ->first();

        $spent = round((float) $totals->net, 2);
        $count = (int) $totals->purchases;

        return [
            'totalSpent' => $spent, // Montant dépensé : articles reçus, remise déduite
            'totalPurchases' => $count,
            'averagePurchaseValue' => $count > 0 ? round($spent / $count, 2) : 0,
            'totalDiscounts' => round((float) $totals->gross - $spent, 2), // Remises sur les articles reçus
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
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            // Recalcul des totaux
            $discount = $this->discount($data['discount'] ?? $purchase->discount, $totalAmount);
            $purchase->update([
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'total_net' => $totalAmount - $discount,
            ]);

            return $purchase;
        });
    }

    /**
     * Get the count of purchases for each state, plus a total.
     *
     * @return Collection
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
     * @param  string  $query  La chaîne de recherche.
     * @param  int  $limit  Le nombre maximum de résultats.
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): Collection
    {
        return Purchase::with('supplier')
            ->whereLike('reference', "%{$query}%")
            ->orWhereHas('supplier', fn ($q) => $q->whereLike('name', "%{$query}%"))
            ->limit($limit)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'type' => 'purchase',
                'id' => $purchase->id,
                'name' => "Achat #{$purchase->reference} (".($purchase->supplier->name ?? 'Sans fournisseur').')',
                'url' => route('admin.purchases.show', $purchase->id),
            ]);
    }

    /**
     * Récupère les achats pour l'API (Tabulator) avec tri dynamique et recherche.
     */
    public function getPurchasesForApi(array $params = [], int $perPage = 15)
    {
        // Filtres de Tabulator (onglets et recherche par colonne) → filtres de la liste (mêmes que l'export)
        $filters = [];
        foreach (is_array($params['filter'] ?? null) ? $params['filter'] : [] as $filter) {
            if (is_array($filter) && isset($filter['field'], $filter['value']) && is_string($filter['value'])) {
                // La colonne « Supplier » du tableau s'appelle supplier (supplier.name : ancien nom, conservé)
                $filters[$filter['field'] === 'supplier.name' ? 'supplier' : $filter['field']] = $filter['value'];
            }
        }
        $query = $this->listQuery($filters, sorted: false);

        // Tri dynamique
        // Seules les colonnes de la table purchases sont triables : un champ inconnu (ex. "supplier.name")
        // provoquait une erreur SQL.
        $sortable = ['reference', 'total_amount', 'discount', 'total_net', 'state', 'created_at'];
        if (! empty($params['sort']) && is_array($params['sort'])) {
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
     * Liste des achats (tableau de la page et export) : statut, référence, fournisseur.
     *
     * @param  array{state?: ?string, reference?: ?string, supplier?: ?string}  $filters
     */
    public function listQuery(array $filters, bool $sorted = true): Builder
    {
        return Purchase::with('supplier')
            ->when(in_array($filters['state'] ?? null, Purchase::STATES, true), fn ($q) => $q->where('state', $filters['state']))
            ->when($filters['reference'] ?? null, fn ($q, $reference) => $q->whereLike('reference', "%{$reference}%"))
            ->when($filters['supplier'] ?? null, fn ($q, $supplier) => $q->whereHas('supplier', fn ($s) => $s->whereLike('name', "%{$supplier}%")))
            ->when($sorted, fn ($q) => $q->latest());
    }

    /**
     * Crée des commandes d'achat groupées par fournisseur à partir d'une liste de produits en rupture.
     *
     * @return array Liste des achats créés
     */
    public function createPurchasesFromShortage(array $itemsToOrder): array
    {
        return DB::transaction(function () use ($itemsToOrder) {
            $itemsBySupplier = collect($itemsToOrder)->groupBy('supplier_id');
            $createdPurchases = [];

            foreach ($itemsBySupplier as $supplierId => $items) {
                // Si le groupement par 'supplier_id' donne une clé vide (null/empty), on passe null
                $actualSupplierId = ($supplierId === '' || $supplierId === null) ? null : (int) $supplierId;
                $createdPurchases[] = $this->processPurchase($actualSupplierId, $items->toArray());
            }

            return $createdPurchases;
        });
    }

    /**
     * Regroupe les produits en rupture de stock par leur dernier fournisseur connu.
     * Une variante est en rupture quand son stock, plus ce qui est déjà commandé (reste à livrer des achats
     * commandés ou partiellement reçus), ne dépasse pas son seuil d'alerte : une commande en cours n'est pas reproposée.
     *
     * @return Collection
     */
    public function getShortageProductsGroupedBySupplier()
    {
        // oq : quantités commandées et pas encore livrées (reste à livrer des commandes en cours).
        // lp : dernière ligne d'un achat commandé ou reçu chez un fournisseur (un brouillon n'engage personne).
        $sql = "
            SELECT
                pc.id, pc.stock, pc.alert_stock, pc.price, pc.price_purchase,
                p.name as product_name,
                p.options as product_options,
                pc.option_values, pc.sku,
                b.name as brand_name,
                COALESCE(oq.ordered_quantity, 0) as ordered_quantity,
                lp.unit_price as last_unit_price,
                lp.supplier_id as last_supplier_id,
                lp.supplier_name as last_supplier_name
            FROM product_colors pc
            INNER JOIN products p ON pc.product_id = p.id
            LEFT JOIN brands b ON pc.brand_id = b.id
            LEFT JOIN (
                SELECT pi.product_color_id, SUM(pi.quantity - pi.received_quantity) as ordered_quantity
                FROM purchase_items pi
                JOIN purchases pur ON pi.purchase_id = pur.id
                WHERE pur.state IN ('Ordered', 'PartiallyReceived')
                GROUP BY pi.product_color_id
            ) oq ON pc.id = oq.product_color_id
            LEFT JOIN (
                SELECT pi.product_color_id, pi.unit_price, s.id as supplier_id, s.name as supplier_name
                FROM purchase_items pi
                JOIN purchases pur ON pi.purchase_id = pur.id
                JOIN suppliers s ON pur.supplier_id = s.id
                WHERE pi.id = (
                    SELECT last_pi.id
                    FROM purchase_items last_pi
                    JOIN purchases last_pur ON last_pi.purchase_id = last_pur.id
                    WHERE last_pi.product_color_id = pi.product_color_id
                      AND last_pur.supplier_id IS NOT NULL
                      AND last_pur.state <> 'Draft'
                    ORDER BY last_pi.created_at DESC, last_pi.id DESC
                    LIMIT 1
                )
            ) lp ON pc.id = lp.product_color_id
            WHERE pc.stock + COALESCE(oq.ordered_quantity, 0) <= pc.alert_stock AND pc.alert_stock > 0
              AND p.archived_at IS NULL
        ";

        $results = DB::select($sql);

        if (empty($results)) {
            return collect();
        }

        $productsWithSupplier = collect($results)->map(function ($item) {
            // Formatage des noms pour la vue (objet simulé)
            $product = new \stdClass;
            foreach ($item as $key => $value) {
                $product->$key = $value;
            }

            $product->label = VariantOptions::label(
                $product->product_name,
                json_decode($product->option_values ?? '[]', true) ?? [],
                $product->brand_name,
                json_decode($product->product_options ?? 'null', true)
            );

            // Coût proposé : dernier prix payé au fournisseur ; sans historique, prix d'achat de la fiche produit
            // (jamais le prix de vente, qui comprend la marge) ; inconnu, il est à saisir sur le bon de commande.
            $product->cost_source = 'last_purchase';
            if (! $product->last_supplier_id) {
                $product->last_supplier_name = __('No supplier assigned');
                $cost = (float) ($product->price_purchase ?? 0);
                $product->last_unit_price = $cost > 0 ? $cost : null;
                $product->cost_source = $cost > 0 ? 'catalog' : 'unknown';
            }

            // Quantité suggérée (indépendante du fournisseur) : remonter à 1,5 × le seuil d'alerte,
            // moins ce qui est déjà commandé
            $product->ordered_quantity = (int) $product->ordered_quantity;
            $missing = $product->alert_stock * 1.5 - $product->stock - $product->ordered_quantity;
            $product->suggested_quantity = max(1, (int) ceil($missing));

            return $product;
        });

        // Regroupement final (Gardé pour la compatibilité de la vue, mais inclut les 'null')
        return $productsWithSupplier->groupBy('last_supplier_id')
            ->map(function ($products, $supplierId) {
                return [
                    'supplier_name' => $products->first()->last_supplier_name,
                    'supplier_id' => $supplierId,
                    'products' => $products,
                ];
            });
    }

    /**
     * Enregistre un bon de commande (création, ou modification d'un brouillon) puis applique l'action :
     * 'draft' reste en brouillon, 'ordered' passe en commandé, 'received' réceptionne (entrée en stock).
     * Tout ou rien : si la réception échoue, l'achat n'est pas enregistré non plus.
     *
     * @param  array  $data  supplier_id, products[], discount, supplier_reference, notes
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
