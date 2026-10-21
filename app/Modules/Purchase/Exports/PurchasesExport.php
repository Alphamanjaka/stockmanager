<?php

namespace App\Modules\Purchase\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Services\PurchaseService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des achats : filtres du tableau de la page (statut, référence, fournisseur), transmis par purchases-index.js.
 */
class PurchasesExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private PurchaseService $purchases) {}

    public function title(): string
    {
        return __('Purchases');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Reference'), 'reference')->width('14%'),
            Column::make(__('Date'), 'created_at')->datetime()->width('13%'),
            Column::make(__('Supplier'), fn (Purchase $purchase) => $purchase->supplier->name ?? null),
            Column::make(__('Supplier ref.'), 'supplier_reference')->width('12%'),
            Column::make(__('Status'), fn (Purchase $purchase) => __($purchase->state))->width('9%'),
            Column::make(__('Total'), 'total_amount')->money()->total()->width('12%'),
            Column::make(__('Discount'), 'discount')->money()->total()->width('11%'),
            Column::make(__('Net total'), 'total_net')->money()->total()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->purchases->listQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        return [
            __('Status') => filled($filters['state'] ?? null) ? __($filters['state']) : null,
            __('Reference') => $filters['reference'] ?? null,
            __('Supplier') => $filters['supplier'] ?? null,
        ];
    }
}
