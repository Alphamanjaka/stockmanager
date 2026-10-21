<?php

namespace App\Modules\Catalog\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Catalog\Models\Color;
use Illuminate\Database\Eloquent\Builder;

class ColorsExport extends ListExport
{
    public function title(): string
    {
        return __('Colors');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Name'), 'name'),
            Column::make(__('Code'), 'code')->width('25%'),
            Column::make(__('Variants'), 'variants_count')->number()->width('15%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return Color::query()
            ->withCount('variants')
            ->withStatus($filters['status'] ?? null)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->search($search))
            ->orderBy('name');
    }
}
