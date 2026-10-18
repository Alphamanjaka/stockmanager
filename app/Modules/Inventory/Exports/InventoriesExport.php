<?php

namespace App\Modules\Inventory\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;

class InventoriesExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private InventoryService $inventories) {}

    public function title(): string
    {
        return 'Stock counts';
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')->width('15%'),
            Column::make('Scope', fn (Inventory $inventory) => $inventory->category->name ?? 'All products'),
            Column::make('Status', fn (Inventory $inventory) => $inventory->isDraft() ? 'Draft' : 'Validated')->width('9%'),
            Column::make('Lines', 'lines_count')->number()->width('7%'),
            Column::make('Counted', 'counted_lines_count')->number()->width('8%'),
            Column::make('Opened', 'created_at')->datetime()->width('12%'),
            Column::make('Opened by', fn (Inventory $inventory) => $inventory->user->name ?? null)->width('13%'),
            Column::make('Validated', 'validated_at')->datetime()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->inventories->listQuery();
    }
}
