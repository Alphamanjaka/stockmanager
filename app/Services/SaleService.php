<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
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
     * Crée une vente sans utilisateur associé.
     */
    public function createSale(array $productsData, float $discount = 0, int $user_id ): Sale
    {
        return DB::transaction(function () use ($productsData, $discount, $user_id) {
            // 1. On prépare la vente
            $sale = new Sale([
                'reference'  => 'SALE-' . strtoupper(Str::random(8)),
                'discount'   => $discount,
                'user_id'    => $user_id, // Toujours mieux de savoir qui a vendu
            ]);

            $totalBrut = 0;
            $itemsToCreate = [];

            foreach ($productsData as $item) {
                // VERROUILLAGE : lockForUpdate empêche d'autres transactions de lire/modifier ce produit
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->quantity_stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour : {$product->name} (Disponible: {$product->quantity_stock})");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalBrut += $subtotal;

                // On prépare les données pour une insertion groupée (plus rapide)
                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];

                // 2. DÉCRÉMENTER via ton StockService
                // Ton StockService doit idéalement mettre à jour le champ quantity_stock du produit
                $this->stockService->removeStock($product->id, $item['quantity'], "Vente {$sale->reference}");
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
        return Sale::with(['items.product'])
            ->latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('reference', 'like', "%{$search}%");
            })
            ->paginate($perPage);
    }

    public function getSaleById($id): Sale
    {
        return Sale::with(['items.product'])->findOrFail($id);
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
}