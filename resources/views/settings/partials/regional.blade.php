<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Regional Configuration</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Currency Symbol</label>
                <input type="text" class="form-control" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '€' }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Currency position</label>
                <select class="form-select" name="currency_position">
                    <option value="after" {{ ($settings['currency_position'] ?? '') == 'after' ? 'selected' : '' }}>After the amount (100 €)</option>
                    <option value="before" {{ ($settings['currency_position'] ?? '') == 'before' ? 'selected' : '' }}>Before the amount (€ 100)</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Timezone</label>
            <select class="form-select" name="timezone">
                @foreach (timezone_identifiers_list() as $timezone)
                    <option value="{{ $timezone }}" {{ ($settings['timezone'] ?? 'UTC') == $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date format</label>
            <select class="form-select" name="date_format">
                <option value="d/m/Y" {{ ($settings['date_format'] ?? '') == 'd/m/Y' ? 'selected' : '' }}>JJ/MM/AAAA (31/12/2024)</option>
                <option value="Y-m-d" {{ ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>AAAA-MM-JJ (2024-12-31)</option>
                <option value="m/d/Y" {{ ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' }}>MM/JJ/AAAA (12/31/2024)</option>
            </select>
        </div>
    </div>
</div>