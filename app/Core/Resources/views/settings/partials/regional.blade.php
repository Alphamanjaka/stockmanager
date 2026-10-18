<x-ui.card title="Regional & currency" subtitle="Currency, time zone and date format used everywhere in the application">
    <div class="row">
        <x-ui.field class="col-md-6" name="currency_symbol" label="Currency symbol" :value="$settings['currency_symbol'] ?? '€'" />
        <x-ui.field class="col-md-6" name="currency_position" label="Currency position">
            <select class="form-select" id="currency_position" name="currency_position">
                <option value="after" @selected(($settings['currency_position'] ?? '') == 'after')>After the amount (100 €)</option>
                <option value="before" @selected(($settings['currency_position'] ?? '') == 'before')>Before the amount (€ 100)</option>
            </select>
        </x-ui.field>
        <x-ui.field class="col-md-6 mb-0" name="timezone" label="Time zone">
            <select class="form-select" id="timezone" name="timezone">
                @foreach (timezone_identifiers_list() as $timezone)
                    <option value="{{ $timezone }}" @selected(($settings['timezone'] ?? 'UTC') == $timezone)>{{ $timezone }}</option>
                @endforeach
            </select>
        </x-ui.field>
        <x-ui.field class="col-md-6 mb-0" name="date_format" label="Date format">
            <select class="form-select" id="date_format" name="date_format">
                <option value="d/m/Y" @selected(($settings['date_format'] ?? '') == 'd/m/Y')>DD/MM/YYYY (31/12/2024)</option>
                <option value="Y-m-d" @selected(($settings['date_format'] ?? '') == 'Y-m-d')>YYYY-MM-DD (2024-12-31)</option>
                <option value="m/d/Y" @selected(($settings['date_format'] ?? '') == 'm/d/Y')>MM/DD/YYYY (12/31/2024)</option>
            </select>
        </x-ui.field>
    </div>
</x-ui.card>
