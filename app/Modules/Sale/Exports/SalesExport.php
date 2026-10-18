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
        return 'Sales';
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')->width('14%'),
            Column::make('Date', 'created_at')->datetime()->width('12%'),
            Column::make('Customer', fn (Sale $sale) => $sale->customer->name ?? null),
            Column::make('Seller', fn (Sale $sale) => $sale->user->name ?? null)->width('13%'),
            Column::make('Payment', 'payment_method')->width('9%'),
            Column::make('Gross total', 'total_brut')->money()->total()->width('12%'),
            Column::make('Discount', 'discount')->money()->total()->width('10%'),
            Column::make('Net total', 'total_net')->money()->total()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->sales->listQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        $date = fn (?string $value) => rescue(fn () => $value ? Carbon::parse($value)->format('d/m/Y') : null, null, false);

        return [
            'Customer' => filled($filters['customer'] ?? null) ? Customer::whereKey((int) $filters['customer'])->value('name') : null,
            'From' => $date($filters['date_from'] ?? null),
            'To' => $date($filters['date_to'] ?? null),
        ];
    }
}
