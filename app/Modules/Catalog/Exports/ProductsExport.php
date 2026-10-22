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
        return __('Products');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Product'), fn (ProductColor $variant) => $variant->label),
            Column::make(__('SKU'), 'sku')->width('13%'),
            Column::make(__('Category'), fn (ProductColor $variant) => $variant->category_name)->width('13%'),
            Column::make(__('Stock'), 'stock')->number()->total()->width('7%'),
            Column::make(__('Alert'), 'alert_stock')->number()->width('6%'),
            Column::make(__('Cost price'), 'price_purchase')->money()->width('11%'),
            Column::make(__('Price'), 'price')->money()->width('11%'),
            Column::make(__('Stock value'), fn (ProductColor $variant) => $variant->stock * $variant->price)->money()->total()->width('12%'),
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
            __('Category') => filled($filters['category'] ?? null) ? (string) Category::whereKey((int) $filters['category'])->value('name') : null,
        ];
    }

    protected function summary(Collection $rows): array
    {
        $low = $rows->filter(fn (ProductColor $variant) => $variant->alert_stock > 0 && $variant->stock <= $variant->alert_stock)->count();

        return [
            __('Variants') => number_format($rows->count()),
            __('Low stock') => __(':count variant(s)', ['count' => number_format($low)]),
        ];
    }
}
