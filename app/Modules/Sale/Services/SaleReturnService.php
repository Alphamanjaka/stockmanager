<?php

namespace App\Modules\Sale\Services;

use App\Core\Services\DocumentNumberService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use App\Modules\Sale\Models\SaleReturn;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Support\Facades\DB;

/**
 * Avoirs : retour d'une partie des articles d'une vente, ou annulation de la vente (tout est rendu).
 * Réservé au back office. La vente n'est jamais modifiée ; l'avoir est numéroté à la suite (AVO-2026-000001).
 */
class SaleReturnService
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumbers,
    ) {}

    /**
     * Enregistre un avoir : les articles rendus reviennent en stock (mouvement « retour », à leur coût figé
     * à la vente) et le montant remboursé compte en négatif dans le chiffre d'affaires du jour.
     * Le remboursement d'une ligne déduit sa part de la remise de la vente ; quand tout est rendu,
     * le total remboursé est exactement le net payé.
     *
     * @param  array<int, int|string|null>  $quantities  [id de ligne de vente => quantité rendue]
     * @throws \Exception quantité supérieure à ce qui reste, ou aucun article rendu
     */
    public function create(int $saleId, array $quantities, string $reason, ?string $refundMethod = null, ?int $userId = null): SaleReturn
    {
        return DB::transaction(function () use ($saleId, $quantities, $reason, $refundMethod, $userId) {
            // Verrou : deux retours simultanés ne peuvent pas rendre deux fois les mêmes articles
            $sale = Sale::with(['items.productColor.product', 'items.productColor.color', 'items.productColor.brand', 'returns.items'])
                ->lockForUpdate()
                ->findOrFail($saleId);

            $returned = $sale->returnedQuantities();
            $lines = [];
            foreach ($sale->items as $item) {
                $quantity = (int) ($quantities[$item->id] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $left = $item->quantity - ($returned[$item->id] ?? 0);
                if ($quantity > $left) {
                    throw new \Exception("Retour impossible pour {$item->productColor->label} : {$left} article(s) restant(s) sur cette vente.");
                }

                $lines[] = [$item, $quantity];
            }

            if (empty($lines)) {
                throw new \Exception('Aucun article à retourner : indiquez au moins une quantité.');
            }

            $rows = $this->refundLines($sale, $lines, $returned);

            $saleReturn = SaleReturn::create([
                'reference' => $this->documentNumbers->next(SaleReturn::PREFIX),
                'sale_id' => $sale->id,
                'user_id' => $userId ?? auth()->id(),
                'reason' => trim($reason),
                'total' => round(array_sum(array_column($rows, 'subtotal')), 2),
                'refund_method' => $refundMethod ?? $sale->payment_method,
            ]);
            $saleReturn->items()->createMany($rows);

            // Retour en stock à leur coût figé à la vente : le coût moyen pondéré en tient compte
            foreach ($rows as $row) {
                $this->stockService->addStock(
                    $row['product_color_id'],
                    $row['quantity'],
                    "Retour {$saleReturn->reference} (vente {$sale->reference})",
                    $row['unit_cost'] === null ? null : (float) $row['unit_cost'],
                    MovementSource::saleReturn($saleReturn->id)
                );
            }

            return $saleReturn->load('items');
        });
    }

    /**
     * Annulation de la vente : avoir de tous les articles qui n'ont pas encore été rendus.
     *
     * @throws \Exception si tout a déjà été rendu
     */
    public function cancel(int $saleId, string $reason, ?string $refundMethod = null, ?int $userId = null): SaleReturn
    {
        $sale = Sale::with(['items', 'returns.items'])->findOrFail($saleId);
        $returned = $sale->returnedQuantities();

        $remaining = $sale->items
            ->mapWithKeys(fn (SaleItem $item) => [$item->id => $item->quantity - ($returned[$item->id] ?? 0)])
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();

        if (empty($remaining)) {
            throw new \Exception("La vente {$sale->reference} est déjà entièrement retournée.");
        }

        return $this->create($saleId, $remaining, $reason, $refundMethod, $userId);
    }

    /**
     * Lignes de l'avoir avec leur remboursement : quantité × prix × (net payé / total brut).
     * Si cet avoir rend tout ce qui restait, le dernier centime d'arrondi est ajusté pour que la vente
     * soit remboursée exactement de son net.
     *
     * @param  list<array{0: SaleItem, 1: int}>  $lines
     * @param  array<int, int>  $returned  quantités déjà rendues par ligne
     */
    private function refundLines(Sale $sale, array $lines, array $returned): array
    {
        $netRatio = (float) $sale->total_brut > 0 ? (float) $sale->total_net / (float) $sale->total_brut : 0;

        $rows = array_map(fn (array $line) => [
            'sale_item_id' => $line[0]->id,
            'product_color_id' => $line[0]->product_color_id,
            'quantity' => $line[1],
            'unit_price' => $line[0]->unit_price,
            'unit_cost' => $line[0]->unit_cost,
            'subtotal' => round($line[1] * (float) $line[0]->unit_price * $netRatio, 2),
        ], $lines);

        $now = collect($lines)->mapWithKeys(fn (array $line) => [$line[0]->id => $line[1]]);
        $everythingReturned = $sale->items->every(
            fn (SaleItem $item) => ($returned[$item->id] ?? 0) + ($now[$item->id] ?? 0) >= $item->quantity
        );

        if ($everythingReturned) {
            $due = round((float) $sale->total_net - $sale->refundedTotal(), 2);
            $last = array_key_last($rows);
            $rows[$last]['subtotal'] = round($rows[$last]['subtotal'] + $due - array_sum(array_column($rows, 'subtotal')), 2);
        }

        return $rows;
    }
}
