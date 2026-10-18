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
        return 'Purchases';
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')->width('14%'),
            Column::make('Date', 'created_at')->datetime()->width('13%'),
            Column::make('Supplier', fn (Purchase $purchase) => $purchase->supplier->name ?? null),
            Column::make('Supplier ref.', 'supplier_reference')->width('12%'),
            Column::make('Status', 'state')->width('9%'),
            Column::make('Total', 'total_amount')->money()->total()->width('12%'),
            Column::make('Discount', 'discount')->money()->total()->width('11%'),
            Column::make('Net total', 'total_net')->money()->total()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->purchases->listQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        return [
            'Status' => $filters['state'] ?? null,
            'Reference' => $filters['reference'] ?? null,
            'Supplier' => $filters['supplier'] ?? null,
        ];
    }
}
