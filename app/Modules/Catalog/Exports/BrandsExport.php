<?php

namespace App\Modules\Catalog\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Builder;

class BrandsExport extends ListExport
{
    public function title(): string
    {
        return 'Brands';
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name'),
            Column::make('Variants', 'variants_count')->number()->total()->width('18%'),
            Column::make('Created', 'created_at')->date()->width('18%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return Brand::forList($filters);
    }
}
