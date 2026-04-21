<?php

namespace App\Services;

use App\Models\ProductColor;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    protected $stockService;
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }
    /**
     * Crée une vente en l'associant à un utilisateur.
     * Si l'ID utilisateur n'est pas fourni, il utilise l'utilisateur authentifié.
     */
    public function createSale(array $productsData, float $discount = 0, ?int $user_id = null): Sale
    {
        $userId = $user_id ?? auth()->id();

        if (is_null($userId)) {
            throw new \Exception("Impossible de créer une vente : aucun utilisateur n'est authentifié ou fourni.");
        }

        return DB::transaction(function () use ($productsData, $discount, $userId) {
            // 1. On prépare la vente
            $sale = new Sale([
                'reference'  => 'SALE-' . strtoupper(Str::random(8)),
                'discount'   => $discount,
                'user_id'    => $userId, // Toujours mieux de savoir qui a vendu
            ]);

            $totalBrut = 0;
            $itemsToCreate = [];



            foreach ($productsData as $item) {
                // VERROUILLAGE : On verrouille la variante spécifique (Produit + Couleur)
                $variant = ProductColor::with('product', 'color')->lockForUpdate()->findOrFail($item['product_color_id']);

                if ($variant->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour : {$variant->product->name} ({$variant->color->name}) (Disponible: {$variant->stock})");
                }

                $subtotal = $variant->product->price * $item['quantity'];
                $totalBrut += $subtotal;

                // On prépare les données pour une insertion groupée (plus rapide)
                $itemsToCreate[] = [
                    'product_color_id' => $variant->id,
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $variant->product->price,
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
     * Liste des ventes simplifiée.
     */
    public function getAllSales(int $perPage = 15, array $filters = [])
    {
        // On retire le 'user' du Eager Loading
        return Sale::with(['items.productColor.product', 'items.productColor.color'])
            ->latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('reference', 'like', "%{$search}%");
            })
            ->paginate($perPage);
    }

    public function getSaleById($id): Sale
    {
        return Sale::with(['items.productColor.product', 'items.productColor.color'])->findOrFail($id);
    }


    public function getSalesStatistics(): array
    {
        return [
            'today_sales_count' => Sale::whereDate('created_at', today())->count(),
            'total_revenue'     => Sale::sum('total_net'),
            'average_sale'      => Sale::avg('total_net') ?? 0,
            'total_discount' => Sale::sum('discount') ?? 0,
        ];
    }
    /**
     * Common logic for top/least sold variants
     */
    private function getVariantSaleStats(string $direction = 'desc')
    {
        return SaleItem::select('product_color_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_color_id')
            ->with(['productColor.product', 'productColor.color'])
            ->orderBy('total_quantity', $direction)
            ->first();
    }

    public function getMostSoldProduct()
    {
        return $this->getVariantSaleStats('desc');
    }
    public function getLeastSoldProduct()
    {
        return $this->getVariantSaleStats('asc');
    }
}
