<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Stock Settings</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Global alert threshold (default)</label>
            <input type="number" class="form-control" name="global_alert_threshold" value="{{ $settings['global_alert_threshold'] ?? 5 }}">
            <div class="form-text">Used if no product-specific threshold is set.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Default VAT rate (%)</label>
            <input type="number" step="0.01" class="form-control" name="default_tax_rate" value="{{ $settings['default_tax_rate'] ?? 20 }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Valuation method</label>
            <select class="form-select" name="stock_valuation_method">
                <option value="FIFO" {{ ($settings['stock_valuation_method'] ?? '') == 'FIFO' ? 'selected' : '' }}>FIFO (First in, first out)</option>
                <option value="CUMP" {{ ($settings['stock_valuation_method'] ?? '') == 'CUMP' ? 'selected' : '' }}>CUMP (Weighted Average Unit Cost)</option>
            </select>
        </div>
    </div>
</div>