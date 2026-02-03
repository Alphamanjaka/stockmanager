<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function processSale(array $items, int $discount = 0)
    {
        return DB::transaction(function () use ($items, $discount) {
            $totalBrut = 0;

            // 1. Créer la vente initiale
            $sale = Sale::create([
                'reference' => 'SALE-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'total_brut' => 0, // Temporaire
                'discount' => $discount,
                'total_net' => 0, // Temporaire
            ]);

            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalBrut += $subtotal;

                // 2. Créer la ligne de vente
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // 3. DÉCRÉMENTER le stock via StockService
                $this->stockService->removeStock($item['product_id'], $item['quantity'], "Vente {$sale->reference}");
            }

            // 4. Mettre à jour les totaux de la vente
            $sale->update(['total_brut' => $totalBrut, 'total_net' => max(0, $totalBrut - $discount)]);

            return $sale;
        });
    }
}
