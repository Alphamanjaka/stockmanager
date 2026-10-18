<?php

namespace App\Modules\Contact\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Contact\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomersExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function title(): string
    {
        return 'Customers';
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->width('20%'),
            Column::make('Phone', 'phone')->width('13%'),
            Column::make('Email', 'email')->width('20%'),
            Column::make('Address', 'address'),
            Column::make('Customer since', 'created_at')->date()->width('11%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? null, ['name', 'created_at'], true) ? $filters['sort'] : 'name';

        return Customer::query()
            ->withStatus($filters['status'] ?? null)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->search($search))
            ->orderBy($sort, ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
    }
}
