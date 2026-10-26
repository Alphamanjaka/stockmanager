<?php

namespace App\Modules\Sale\Exports;

use App\Core\Exports\Column;
use App\Core\Exports\ListExport;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Services\AbcAnalysisService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Analyse ABC : mêmes paramètres que la page (période ?days=, classe ?class=). Les lignes sont calculées
 * (classement et cumul), pas lues d'une requête : rows() est redéfinie.
 */
class AbcAnalysisExport extends ListExport
{
    protected string $orientation = 'landscape';

    public function __construct(private AbcAnalysisService $abc) {}

    public function title(): string
    {
        return __('ABC analysis');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Class'), 'class')->width('6%'),
            Column::make(__('SKU'), 'variant.sku')->width('12%'),
            Column::make(__('Product'), fn ($line) => $line->variant->label),
            Column::make(__('Quantity sold'), 'quantity')->number()->total()->width('9%'),
            Column::make(__('Revenue'), 'revenue')->money()->total()->width('12%'),
            Column::make(__('Share'), fn ($line) => $line->share.' %')->width('8%'),
            Column::make(__('Cumulative share'), fn ($line) => $line->cumulative.' %')->width('9%'),
            Column::make(__('Stock'), 'variant.stock')->number()->total()->width('7%'),
            Column::make(__('Stock value (cost)'), 'stock_value')->money()->total()->width('12%'),
        ];
    }

    public function rows(array $filters, array $ids = [], ?int $limit = null): Collection
    {
        $lines = $this->abc->lines((int) ($filters['days'] ?? AbcAnalysisService::DEFAULT_PERIOD), $filters['class'] ?? null);

        return $limit ? $lines->take($limit) : $lines;
    }

    /** Non utilisée : les lignes viennent du calcul (rows). */
    protected function query(array $filters): Builder
    {
        return ProductColor::query();
    }

    protected function filterLabels(array $filters): array
    {
        $days = (int) ($filters['days'] ?? AbcAnalysisService::DEFAULT_PERIOD);

        return [
            __('Period') => __('Last :days days', ['days' => in_array($days, AbcAnalysisService::PERIODS, true) ? $days : AbcAnalysisService::DEFAULT_PERIOD]),
            __('Class') => in_array($filters['class'] ?? null, AbcAnalysisService::CLASSES, true) ? $filters['class'] : null,
        ];
    }
}
