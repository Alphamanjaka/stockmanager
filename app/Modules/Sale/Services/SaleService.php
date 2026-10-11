<?php

namespace App\Modules\Sale\Services;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Stock\Services\StockService;
use App\Modules\Contact\Services\CustomerService;

class SaleService
{
    public function __construct(protected StockService $stockService, protected CustomerService $customerService) {}
    /**
     * Crée une vente en l'associant à un utilisateur.
     * Si l'ID utilisateur n'est pas fourni, il utilise l'utilisateur authentifié.
     *
     * @param array $customer client facultatif : ['customer_id' => id] pour un client existant,
     *                        ou ['new_customer_name' => ..., 'new_customer_phone' => ...] pour le créer
     */
    public function createSale(array $productsData, float $discount = 0, ?int $user_id = null, array $customer = []): Sale
    {
        $userId = $user_id ?? auth()->id();

        if (is_null($userId)) {
            throw new \Exception("Impossible de créer une vente : aucun utilisateur n'est authentifié ou fourni.");
        }

        return DB::transaction(function () use ($productsData, $discount, $userId, $customer) {
            // 1. On prépare la vente (un client créé ici est annulé si la vente échoue)
            $sale = new Sale([
                'customer_id' => $this->resolveCustomerId($customer),
                'reference'  => 'SALE-' . strtoupper(Str::random(8)),
                'discount'   => $discount,
                'user_id'    => $userId, // Toujours mieux de savoir qui a vendu
                'company_name' => settings('company_name', 'StockMaster Pro'),
                'company_email' => settings('company_email', ''),
                'company_phone' => settings('company_phone', ''),
                'company_address' => settings('company_address', ''),
            ]);

            $totalBrut = 0;
            $itemsToCreate = [];



            foreach ($productsData as $item) {
                // VERROUILLAGE : On verrouille la variante spécifique (Produit + Couleur)
                $variant = ProductColor::with('product', 'color', 'brand')->lockForUpdate()->findOrFail($item['product_color_id']);

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
                    'subtotal'         => $subtotal,
                ];

                // 2. DÉCRÉMENTER via le StockService (qui doit maintenant accepter product_color_id)
                $this->stockService->removeStock($variant->id, $item['quantity'], "Vente {$sale->reference}");
            }




            // 3. Sauvegarde de la vente avec les vrais totaux du premier coup
            $sale->total_brut = $totalBrut;
            $sale->total_net = max(0, $totalBrut - $discount);
            $sale->save();

            // 4. Création groupée des lignes (une seule requête SQL au lieu d'une par produit)
            $sale->items()->createMany($itemsToCreate);

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
     * Liste des ventes simplifiée.
     */
    public function getAllSales(int $perPage = 15, array $filters = [])
    {
        return Sale::with(['items.productColor.product', 'items.productColor.color', 'items.productColor.brand', 'customer', 'user'])
            ->latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereLike('reference', "%{$search}%");
            })
            ->when($filters['customer'] ?? null, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($filters['user'] ?? null, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->paginate($perPage);
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