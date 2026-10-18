<?php

namespace App\Modules\Catalog\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Database\Eloquent\Builder;

class CategoriesExport extends ListExport
{
    public function __construct(private CategoryService $categories) {}

    public function title(): string
    {
        return 'Categories';
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name'),
            Column::make('Parent', fn (Category $category) => $category->parent->name ?? 'Root'),
            Column::make('Description', 'description'),
            Column::make('Products', 'products_count')->number()->total()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        // Même requête que la page (CategoryService::getAllCategory), sans pagination
        return $this->categories->applyFilters(
            Category::with('parent')->withCount('products')->withStatus($filters['status'] ?? null),
            $filters
        );
    }
}
