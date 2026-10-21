<?php

namespace App\Modules\Stock\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Historique des mouvements de stock : mêmes filtres que la page (recherche, type, période).
 */
class StockMovementsExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private StockService $stock) {}

    public function title(): string
    {
        return 'Stock movements';
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'created_at')->datetime()->width('11%'),
            Column::make('Product', fn (StockMovement $movement) => $movement->productColor?->label),
            Column::make('Type', fn (StockMovement $movement) => $movement->kind()[1])->width('9%'),
            Column::make('Quantity', fn (StockMovement $movement) => ($movement->type === 'in' ? 1 : -1) * abs($movement->quantity))
                ->number()->width('8%'),
            Column::make('Before', 'stock_before')->number()->width('7%'),
            Column::make('After', 'stock_after')->number()->width('7%'),
            Column::make('Reason', 'reason')->width('20%'),
            Column::make('By', fn (StockMovement $movement) => $movement->user?->name)->width('10%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->stock->stockMovementsQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        $date = fn (?string $value) => rescue(fn () => $value ? Carbon::parse($value)->format('d/m/Y') : null, null, false);

        return [
            'Type' => ['in' => 'Stock in', 'out' => 'Stock out'][$filters['type'] ?? ''] ?? null,
            'From' => $date($filters['date_from'] ?? null),
            'To' => $date($filters['date_to'] ?? null),
        ];
    }
}
