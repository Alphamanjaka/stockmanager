<x-ui.card title="Stock & products" subtitle="Default values for stock alerts, taxes and valuation">
    <div class="row">
        <x-ui.field class="col-md-6" name="global_alert_threshold" label="Default alert threshold" type="number" min="0"
            hint="Used when a variant has no threshold of its own." :value="$settings['global_alert_threshold'] ?? 5" />
        <x-ui.field class="col-md-6" name="default_tax_rate" label="Default VAT rate (%)" type="number" step="0.01" min="0"
            :value="$settings['default_tax_rate'] ?? 20" />
        <x-ui.field class="col-md-6 mb-0" name="stock_valuation_method" label="Valuation method">
            <select class="form-select" id="stock_valuation_method" name="stock_valuation_method">
                <option value="FIFO" @selected(($settings['stock_valuation_method'] ?? '') == 'FIFO')>FIFO (first in, first out)</option>
                <option value="CUMP" @selected(($settings['stock_valuation_method'] ?? '') == 'CUMP')>Weighted average unit cost</option>
            </select>
        </x-ui.field>
    </div>
</x-ui.card>
