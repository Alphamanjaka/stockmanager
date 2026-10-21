<?php

namespace App\Modules\Sale\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Contact\Models\Customer;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Liste des ventes du back office : mêmes filtres que la page (recherche, client, période).
 */
class SalesExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private SaleService $sales) {}

    public function title(): string
    {
        return __('Sales');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Reference'), 'reference')->width('12%'),
            Column::make(__('Date'), 'created_at')->datetime()->width('11%'),
            Column::make(__('Customer'), fn (Sale $sale) => $sale->customer->name ?? null),
            Column::make(__('Seller'), fn (Sale $sale) => $sale->user->name ?? null)->width('11%'),
            Column::make(__('Payment'), fn (Sale $sale) => __("pos.methods.{$sale->payment_method}"))->width('7%'),
            Column::make(__('Gross total'), 'total_brut')->money()->total()->width('11%'),
            Column::make(__('Discount'), 'discount')->money()->total()->width('9%'),
            Column::make(__('Net total'), 'total_net')->money()->total()->width('11%'),
            // Avoirs de la vente (retours d'articles, annulation)
            Column::make(__('Refunded'), fn (Sale $sale) => $sale->refundedTotal() ?: null)->money()->total()->width('9%'),
            // Avoirs déduits ; vide quand le coût d'un article n'est pas connu (compté 0 dans le total)
            Column::make(__('Margin'), fn (Sale $sale) => $sale->grossMargin())->money()->total()->width('11%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->sales->listQuery($filters)->with(['items', 'returns.items']);
    }

    protected function filterLabels(array $filters): array
    {
        $date = fn (?string $value) => rescue(fn () => $value ? Carbon::parse($value)->format('d/m/Y') : null, null, false);

        return [
            __('Customer') => filled($filters['customer'] ?? null) ? Customer::whereKey((int) $filters['customer'])->value('name') : null,
            __('From') => $date($filters['date_from'] ?? null),
            __('To') => $date($filters['date_to'] ?? null),
        ];
    }
}
