<?php

namespace App\Modules\Sale\Services;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use App\Modules\Contact\Services\CustomerService;

class SaleService
{
    public function __construct(protected StockService $stockService, protected CustomerService $customerService) {}
    /**
     * Crée une vente en l'associant à un utilisateur.
     * Si l'ID utilisateur n'est pas fourni, il utilise l'utilisateur authentifié.
     *
     * @param array $details client et encaissement, tous facultatifs :
     *                       - ['customer_id' => id] pour un client existant,
     *                         ou ['new_customer_name' => ..., 'new_customer_phone' => ...] pour le créer
     *                       - ['payment_method' => 'cash'|'mobile_money'|'card', 'amount_received' => somme remise]
     */
    public function createSale(array $productsData, float $discount = 0, ?int $user_id = null, array $details = []): Sale
    {
        $userId = $user_id ?? auth()->id();

        if (is_null($userId)) {
            throw new \Exception("Impossible de créer une vente : aucun utilisateur n'est authentifié ou fourni.");
        }

        return DB::transaction(function () use ($productsData, $discount, $userId, $details) {
            // 1. On prépare la vente (un client créé ici est annulé si la vente échoue)
            $sale = new Sale([
                'customer_id' => $this->resolveCustomerId($details),
                'reference'  => 'SALE-' . strtoupper(Str::random(8)),
                'discount'   => $discount,
                'user_id'    => $userId, // Toujours mieux de savoir qui a vendu
                'payment_method' => $details['payment_method'] ?? Sale::PAYMENT_CASH,
                'company_name' => settings('company_name', 'StockMaster Pro'),
                'company_email' => settings('company_email', ''),
                'company_phone' => settings('company_phone', ''),
                'company_address' => settings('company_address', ''),
                // Devise figée comme les coordonnées : le reçu reste juste si le paramètre change
                'currency_symbol' => settings('currency_symbol', 'MGA'),
            ]);

            $totalBrut = 0;
            $itemsToCreate = [];



            foreach ($productsData as $item) {
                // VERROUILLAGE : On verrouille la variante spécifique (Produit + Couleur)
                $variant = ProductColor::with('product', 'color', 'brand')->lockForUpdate()->findOrFail($item['product_color_id']);

                // Un produit archivé n'est plus en vente (la caisse ne le propose plus ; refusé aussi ici)
                if ($variant->product?->isArchived()) {
                    throw new \Exception("Produit archivé, il n'est plus en vente : {$variant->label}");
                }

                if ($variant->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour : {$variant->label} (Disponible: {$variant->stock})");
                }

                $subtotal = $variant->price * $item['quantity'];
                $totalBrut += $subtotal;

                // On prépare les données pour une insertion groupée (plus rapide)
                $itemsToCreate[] = [
                    'product_color_id' => $variant->id,
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $variant->price,
                    // Coût figé comme le prix : la marge de la vente ne bouge plus quand le coût évolue
                    'unit_cost'        => (float) $variant->price_purchase > 0 ? $variant->price_purchase : null,
                    'subtotal'         => $subtotal,
                ];
            }

            // 2. Sauvegarde de la vente avec les vrais totaux du premier coup
            $sale->total_brut = $totalBrut;
            $sale->total_net = max(0, $totalBrut - $discount);
            $sale->amount_received = $this->amountReceived($sale, $details['amount_received'] ?? null);
            $sale->save();

            // 3. Création groupée des lignes (une seule requête SQL au lieu d'une par produit)
            $sale->items()->createMany($itemsToCreate);

            // 4. Sortie de stock, une fois la vente enregistrée : chaque mouvement cite la vente.
            // Les variantes sont verrouillées depuis leur lecture : le stock vérifié plus haut n'a pas bougé.
            foreach ($itemsToCreate as $line) {
                $this->stockService->removeStock($line['product_color_id'], $line['quantity'], "Vente {$sale->reference}", MovementSource::sale($sale->id));
            }

            return $sale;
        });
    }

    /**
     * Client de la vente : existant, créé à la volée (nom saisi), ou aucun.
     */
    private function resolveCustomerId(array $customer): ?int
    {
        if (!empty($customer['customer_id'])) {
            return (int) $customer['customer_id'];
        }

        if (!empty($customer['new_customer_name'])) {
            return $this->customerService->create([
                'name' => $customer['new_customer_name'],
                'phone' => $customer['new_customer_phone'] ?? null,
            ])->id;
        }

        return null;
    }

    /**
     * Somme encaissée. En espèces, la somme remise doit couvrir le net à payer (calculé ici avec les prix
     * de la base, pas ceux affichés en caisse) ; sans somme saisie ou pour un autre mode, c'est le montant exact.
     */
    private function amountReceived(Sale $sale, mixed $received): float
    {
        if ($sale->payment_method !== Sale::PAYMENT_CASH || $received === null || $received === '') {
            return (float) $sale->total_net;
        }

        if (round((float) $received, 2) < round((float) $sale->total_net, 2)) {
            throw new \Exception("Montant reçu insuffisant : {$received} reçu pour {$sale->total_net} à payer.");
        }

        return (float) $received;
    }

    /**
     * Liste des ventes simplifiée.
     */
    public function getAllSales(int $perPage = 15, array $filters = [])
    {
        return $this->listQuery($filters)
            ->with(['items.productColor.product', 'items.productColor.color', 'items.productColor.brand'])
            ->paginate($perPage);
    }

    /**
     * Liste des ventes (page et export) : référence ou client, client, vendeur, période (bornes incluses).
     *
     * @param array{search?: ?string, customer?: ?int, user?: ?int, date_from?: ?string, date_to?: ?string} $filters
     */
    public function listQuery(array $filters = []): Builder
    {
        $date = fn (?string $value) => $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;

        return Sale::with(['customer', 'user'])
            ->latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q->whereLike('reference', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('name', "%{$search}%")));
            })
            ->when($filters['customer'] ?? null, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($filters['user'] ?? null, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($date($filters['date_from'] ?? null), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($date($filters['date_to'] ?? null), fn ($query, $to) => $query->whereDate('created_at', '<=', $to));
    }

    public function getSaleById(int $id): Sale
    {
        return Sale::with(['items.productColor.product', 'items.productColor.color', 'items.productColor.brand', 'customer', 'user'])->findOrFail($id);
    }

    /**
     * Vente d'un vendeur : 404 si elle appartient à un autre vendeur (même réponse qu'une vente inexistante).
     */
    public function getSaleForSeller(int $id, int $userId): Sale
    {
        return Sale::with(['items.productColor.product', 'items.productColor.color', 'items.productColor.brand', 'customer', 'user'])
            ->where('user_id', $userId)
            ->findOrFail($id);
    }


    /**
     * Statistiques de ventes ; $userId les limite aux ventes d'un vendeur (front office).
     */
    public function getSalesStatistics(?int $userId = null): array
    {
        $sales = fn () => Sale::query()->when($userId, fn ($q) => $q->where('user_id', $userId));

        return [
            'today_sales_count' => $sales()->whereDate('created_at', today())->count(),
            'total_revenue'     => $sales()->sum('total_net'),
            'average_sale'      => $sales()->avg('total_net') ?? 0,
            'total_discount' => $sales()->sum('discount') ?? 0,
        ];
    }
    /**
     * Marge brute des ventes d'une période (toutes les ventes sans bornes), avec les coûts figés à la vente.
     * Une vente dont le coût d'une ligne n'est pas connu est laissée de côté (excluded) : la compter
     * gonflerait la marge.
     *
     * @return array{revenue: float, cost: float, margin: float, rate: ?float, excluded: int}
     *         rate : marge en % du net encaissé des ventes comptées
     */
    public function getGrossMargin(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $costs = SaleItem::query()
            ->select('sale_id')
            ->selectRaw('SUM(quantity * unit_cost) as cost')
            ->selectRaw('COUNT(*) - COUNT(unit_cost) as unknown_costs')
            ->groupBy('sale_id');

        $totals = Sale::query()
            ->joinSub($costs, 'costs', 'costs.sale_id', '=', 'sales.id')
            ->when($from && $to, fn ($query) => $query->whereBetween('sales.created_at', [$from, $to]))
            ->selectRaw('SUM(CASE WHEN costs.unknown_costs = 0 THEN sales.total_net ELSE 0 END) as revenue')
            ->selectRaw('SUM(CASE WHEN costs.unknown_costs = 0 THEN costs.cost ELSE 0 END) as cost')
            ->selectRaw('SUM(CASE WHEN costs.unknown_costs > 0 THEN 1 ELSE 0 END) as excluded')
            ->toBase()
            ->first();

        $revenue = round((float) $totals->revenue, 2);
        $cost = round((float) $totals->cost, 2);
        $margin = round($revenue - $cost, 2);

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'margin' => $margin,
            'rate' => $revenue > 0 ? round($margin / $revenue * 100, 1) : null,
            'excluded' => (int) $totals->excluded,
        ];
    }

    /**
     * Common logic for top/least sold variants
     * 
     */
    private function getVariantSaleStats(string $direction = 'desc'): ?SaleItem
    {
        return SaleItem::select('product_color_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_color_id')
            ->with(['productColor.product', 'productColor.color'])
            ->orderBy('total_quantity', $direction)
            ->first();
    }

    public function getMostSoldProduct(): ?SaleItem
    {
        return $this->getVariantSaleStats('desc');
    }
    public function getLeastSoldProduct()
    {
        return $this->getVariantSaleStats('asc');
    }

    /**
     * Recherche les ventes pour la recherche globale.
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        return Sale::whereLike('reference', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(fn(Sale $sale) => [
                'type' => 'sale',
                'id' => $sale->id,
                'name' => "Vente #{$sale->reference}",
                'url' => route('admin.sales.show', $sale->id),
            ]);
    }
}