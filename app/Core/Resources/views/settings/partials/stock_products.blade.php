<x-ui.card :title="__('Stock & products')" :subtitle="__('Default values for stock alerts, prices and valuation')">
    <div class="row">
        <x-ui.field class="col-md-6" name="global_alert_threshold" :label="__('Default alert threshold')" type="number" min="0"
            :hint="__('Used when a variant has no threshold of its own.')" :value="$settings['global_alert_threshold'] ?? 5" />
        <x-ui.field class="col-md-6" name="default_margin_percent" :label="__('Default margin on cost (%)')" type="number" step="0.01"
            min="0" max="1000" :placeholder="__('e.g. 30')" :value="$settings['default_margin_percent'] ?? ''"
            :hint="__('New products: the selling price is filled in from the purchase price (cost 10,000 + 30 % = 13,000) and stays editable. Leave empty to type prices by hand.')" />
        {{-- Réapprovisionnement (PurchaseService::getShortageProductsGroupedBySupplier) --}}
        <x-ui.field class="col-md-6" name="restock_sales_period" :label="__('Sales speed measured over')">
            <select class="form-select" id="restock_sales_period" name="restock_sales_period">
                @foreach ([30, 90] as $days)
                    <option value="{{ $days }}" @selected((int) ($settings['restock_sales_period'] ?? 30) === $days)>{{ __(':count days', ['count' => $days]) }}</option>
                @endforeach
            </select>
        </x-ui.field>
        <x-ui.field class="col-md-6" name="default_lead_time_days" :label="__('Default delivery lead time (days)')" type="number" min="0"
            max="365" :hint="__('Used when the supplier has no lead time of its own.')" :value="$settings['default_lead_time_days'] ?? 7" />
        <x-ui.field class="col-md-6" name="restock_coverage_days" :label="__('Days of sales to cover (days)')" type="number" min="0"
            max="365" :hint="__('Restocking orders enough for these days of sales once the order is delivered, plus the alert threshold in reserve.')"
            :value="$settings['restock_coverage_days'] ?? 30" />
        {{-- Pas de choix : le CUMP est la seule méthode calculée (StockService, à chaque réception) --}}
        <div class="col-md-6 ui-field mb-0">
            <div class="form-label">{{ __('Valuation method') }}</div>
            <p class="mb-0 fw-semibold">{{ __('Weighted average cost') }}</p>
            <div class="form-text">{{ __('The purchase price of each variant is recomputed at each received purchase. The stock is valued at this cost.') }}</div>
        </div>
    </div>
</x-ui.card>
