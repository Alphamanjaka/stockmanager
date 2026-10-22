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
        return __('Stock movements');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Date'), 'created_at')->datetime()->width('11%'),
            Column::make(__('Product'), fn (StockMovement $movement) => $movement->productColor?->label),
            Column::make(__('Type'), fn (StockMovement $movement) => $movement->kind()[1])->width('9%'),
            Column::make(__('Quantity'), fn (StockMovement $movement) => ($movement->type === 'in' ? 1 : -1) * abs($movement->quantity))
                ->number()->width('8%'),
            Column::make(__('Before'), 'stock_before')->number()->width('7%'),
            Column::make(__('After'), 'stock_after')->number()->width('7%'),
            Column::make(__('Reason'), 'reason')->width('20%'),
            Column::make(__('By'), fn (StockMovement $movement) => $movement->user?->name)->width('10%'),
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
            __('Type') => ['in' => __('Stock in'), 'out' => __('Stock out')][$filters['type'] ?? ''] ?? null,
            __('From') => $date($filters['date_from'] ?? null),
            __('To') => $date($filters['date_to'] ?? null),
        ];
    }
}
