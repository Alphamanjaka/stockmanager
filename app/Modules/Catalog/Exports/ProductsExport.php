<?php

namespace App\Modules\Catalog\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\ProductColorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Liste des produits (une ligne par variante) : la page admin/products, mêmes filtres et même tri.
 */
class ProductsExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private ProductColorService $variants) {}

    public function title(): string
    {
        return 'Products';
    }

    public function columns(): array
    {
        return [
            Column::make('Product', fn (ProductColor $variant) => $variant->label),
            Column::make('SKU', 'sku')->width('13%'),
            Column::make('Category', fn (ProductColor $variant) => $variant->category_name)->width('13%'),
            Column::make('Stock', 'stock')->number()->total()->width('7%'),
            Column::make('Alert', 'alert_stock')->number()->width('6%'),
            Column::make('Cost price', 'price_purchase')->money()->width('11%'),
            Column::make('Price', 'price')->money()->width('11%'),
            Column::make('Stock value', fn (ProductColor $variant) => $variant->stock * $variant->price)->money()->total()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->variants->listQuery($filters);
    }

    /** Les cases de la liste portent le produit : toutes ses variantes sont exportées. */
    protected function applySelection(Builder $query, array $ids): void
    {
        $query->whereIn('product_colors.product_id', $ids);
    }

    protected function filterLabels(array $filters): array
    {
        return [
            'Category' => filled($filters['category'] ?? null) ? (string) Category::whereKey((int) $filters['category'])->value('name') : null,
        ];
    }

    protected function summary(Collection $rows): array
    {
        $low = $rows->filter(fn (ProductColor $variant) => $variant->alert_stock > 0 && $variant->stock <= $variant->alert_stock)->count();

        return [
            'Variants' => number_format($rows->count()),
            'Low stock' => number_format($low).' variant(s)',
        ];
    }
}
