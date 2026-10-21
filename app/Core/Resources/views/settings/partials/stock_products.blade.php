<x-ui.card :title="__('Stock & products')" :subtitle="__('Default values for stock alerts, prices, taxes and valuation')">
    <div class="row">
        <x-ui.field class="col-md-6" name="global_alert_threshold" :label="__('Default alert threshold')" type="number" min="0"
            :hint="__('Used when a variant has no threshold of its own.')" :value="$settings['global_alert_threshold'] ?? 5" />
        <x-ui.field class="col-md-6" name="default_tax_rate" :label="__('Default VAT rate (%)')" type="number" step="0.01" min="0"
            :value="$settings['default_tax_rate'] ?? 20" />
        <x-ui.field class="col-md-6" name="default_margin_percent" :label="__('Default margin on cost (%)')" type="number" step="0.01"
            min="0" max="1000" :placeholder="__('e.g. 30')" :value="$settings['default_margin_percent'] ?? ''"
            :hint="__('New products: the selling price is filled in from the purchase price (cost 10,000 + 30 % = 13,000) and stays editable. Leave empty to type prices by hand.')" />
        {{-- Pas de choix : le CUMP est la seule méthode calculée (StockService, à chaque réception) --}}
        <div class="col-md-6 ui-field mb-0">
            <div class="form-label">{{ __('Valuation method') }}</div>
            <p class="mb-0 fw-semibold">{{ __('Weighted average cost') }}</p>
            <div class="form-text">{{ __('The purchase price of each variant is recomputed at each received purchase. The stock is valued at this cost.') }}</div>
        </div>
    </div>
</x-ui.card>
