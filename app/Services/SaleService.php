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
    public function createSale(array $productsData, float $discount = 0): Sale
    {
        return DB::transaction(function () use ($productsData, $discount) {
            // 1. Créer la vente d'abord (avec des montants temporaires)
            $sale = Sale::create([
                'reference'    => 'SALE-' . strtoupper(Str::random(8)),
                'total_brut' => 0,
                'discount'     => $discount,
                'total_net' => 0,
            ]);

            $totalAmount = 0;

            foreach ($productsData as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->quantity_stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour : {$product->name} ");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                // 2. Créer les lignes de vente via la relation hasMany 'items'
                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                // 3. DÉCRÉMENTER le stock via StockService
                $this->stockService->removeStock($product->id, $item['quantity'],   "Vente {$sale->reference}");
            }

            // 4. Mettre à jour les totaux de la vente
            $sale->update([
                'total_brut' => $totalAmount,
                'total_net' => $totalAmount - $discount,
            ]);

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
            'total_discount' => Sale::sum('discount') ??0,
        ];
    }
}