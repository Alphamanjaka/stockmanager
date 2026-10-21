<?php

namespace App\Modules\Contact\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Contact\Services\SupplierService;
use Illuminate\Database\Eloquent\Builder;

class SuppliersExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private SupplierService $suppliers) {}

    public function title(): string
    {
        return __('Suppliers');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Name'), 'name')->width('20%'),
            Column::make(__('Phone'), 'phone')->width('13%'),
            Column::make(__('Email'), 'email')->width('20%'),
            Column::make(__('Address'), 'address'),
            Column::make(__('Notes'), 'notes'),
        ];
    }

    protected function query(array $filters): Builder
    {
        // Même filtre et même tri que la page (SupplierService::getAllSuppliers)
        return $this->suppliers->applyFilters(Supplier::query()->withStatus($filters['status'] ?? null), $filters);
    }
}
