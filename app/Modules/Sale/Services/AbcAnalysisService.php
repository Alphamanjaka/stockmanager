<?php

namespace App\Modules\Sale\Services;

use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Analyse ABC des variantes : classement par chiffre d'affaires sur une période.
 *
 * - A : les variantes qui font les premiers 80 % du chiffre d'affaires (à ne jamais laisser en rupture) ;
 * - B : les 15 % suivants ;
 * - C : le reste, dont les variantes en vente qui ne se sont pas vendues sur la période.
 *
 * Le chiffre d'affaires d'une ligne est son sous-total, part de la remise de la vente déduite, moins les avoirs
 * de cette ligne. Il porte sur les ventes de la période (un avoir compte avec la vente qu'il corrige).
 */
class AbcAnalysisService
{
    /** Périodes proposées, en jours. */
    public const PERIODS = [30, 90, 180, 365];

    public const DEFAULT_PERIOD = 90;

    /** Part cumulée du chiffre d'affaires (en %) jusqu'où vont les classes A et B. */
    public const THRESHOLDS = ['A' => 80, 'B' => 95];

    public const CLASSES = ['A', 'B', 'C'];

    /**
     * @return array{
     *     days: int, from: Carbon, revenue: float,
     *     lines: Collection<int, object{variant: ProductColor, quantity: int, revenue: float, share: float, cumulative: float, class: string, stock_value: float}>,
     *     classes: array<string, array{count: int, revenue: float, share: float, stock_value: float}>
     * }
     */
    public function analyse(int $days = self::DEFAULT_PERIOD): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : self::DEFAULT_PERIOD;
        $from = now()->subDays($days)->startOfDay();

        $sold = $this->soldSince($from);
        $returned = $this->returnedSince($from);

        // Variantes vendues sur la période (même archivées depuis), et celles encore en vente qui ne l'ont pas été
        $variants = ProductColor::with(['product', 'color', 'brand'])
            ->where(fn ($query) => $query->whereIn('id', $sold->keys())
                ->orWhereHas('product', fn ($product) => $product->active()))
            ->get();

        $lines = $variants
            ->map(function (ProductColor $variant) use ($sold, $returned) {
                $sale = $sold->get($variant->id);
                $return = $returned->get($variant->id);

                return (object) [
                    'variant' => $variant,
                    'quantity' => (int) ($sale->quantity ?? 0) - (int) ($return->quantity ?? 0),
                    'revenue' => round((float) ($sale->revenue ?? 0) - (float) ($return->revenue ?? 0), 2),
                    'stock_value' => round($variant->stock * (float) $variant->price_purchase, 2),
                ];
            })
            ->sort(fn ($a, $b) => [$b->revenue, $b->quantity, $a->variant->id] <=> [$a->revenue, $a->quantity, $b->variant->id])
            ->values();

        $total = round($lines->sum(fn ($line) => max(0, $line->revenue)), 2);
        $before = 0.0; // part cumulée des variantes mieux classées

        foreach ($lines as $line) {
            $line->share = $total > 0 && $line->revenue > 0 ? round($line->revenue / $total * 100, 2) : 0.0;
            // Une variante est A tant que les mieux classées n'atteignent pas 80 % : celle qui franchit le seuil en fait partie
            $line->class = match (true) {
                $line->revenue <= 0 => 'C',
                $before < self::THRESHOLDS['A'] => 'A',
                $before < self::THRESHOLDS['B'] => 'B',
                default => 'C',
            };
            $before += $line->share;
            $line->cumulative = round(min(100, $before), 2);
        }

        return [
            'days' => $days,
            'from' => $from,
            'revenue' => $total,
            'lines' => $lines,
            'classes' => $this->summarise($lines, $total),
        ];
    }

    /** Lignes d'une classe ('A', 'B', 'C'), ou toutes. */
    public function lines(int $days = self::DEFAULT_PERIOD, ?string $class = null): Collection
    {
        $lines = $this->analyse($days)['lines'];

        return in_array($class, self::CLASSES, true) ? $lines->where('class', $class)->values() : $lines;
    }

    /** Quantités et chiffre d'affaires (remise répartie) des ventes de la période, par variante. */
    private function soldSince(Carbon $from): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.created_at', '>=', $from)
            ->groupBy('sale_items.product_color_id')
            ->select('sale_items.product_color_id')
            ->selectRaw('SUM(sale_items.quantity) AS quantity')
            ->selectRaw('SUM(CASE WHEN sales.total_brut > 0 THEN sale_items.subtotal * sales.total_net / sales.total_brut ELSE 0 END) AS revenue')
            ->get()
            ->keyBy('product_color_id');
    }

    /** Articles rendus sur les ventes de la période, par variante (le sous-total d'un avoir déduit déjà la remise). */
    private function returnedSince(Carbon $from): Collection
    {
        return DB::table('sale_return_items')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.created_at', '>=', $from)
            ->groupBy('sale_return_items.product_color_id')
            ->select('sale_return_items.product_color_id')
            ->selectRaw('SUM(sale_return_items.quantity) AS quantity')
            ->selectRaw('SUM(sale_return_items.subtotal) AS revenue')
            ->get()
            ->keyBy('product_color_id');
    }

    /** Nombre de variantes, chiffre d'affaires et stock (au coût) de chaque classe. */
    private function summarise(Collection $lines, float $total): array
    {
        return collect(self::CLASSES)->mapWithKeys(function (string $class) use ($lines, $total) {
            $group = $lines->where('class', $class);
            $revenue = round($group->sum(fn ($line) => max(0, $line->revenue)), 2);

            return [$class => [
                'count' => $group->count(),
                'revenue' => $revenue,
                'share' => $total > 0 ? round($revenue / $total * 100, 1) : 0.0,
                'stock_value' => round($group->sum('stock_value'), 2),
            ]];
        })->all();
    }
}
