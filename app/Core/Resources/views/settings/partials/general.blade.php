<x-ui.card :title="__('Company')" :subtitle="__('Shown on invoices, receipts and purchase orders')">
    <div class="ui-form-section">
        <div class="row">
            <x-ui.field class="col-md-6" name="company_name" :label="__('Store / company name')" :value="$settings['company_name'] ?? ''" />
            <x-ui.field class="col-md-6" name="company_logo" :label="__('Logo')" type="file" accept="image/*" />
            @if (!empty($settings['company_logo']))
                <div class="col-12 mb-3">
                    <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="{{ __('Current logo') }}" class="border rounded p-1"
                        style="max-height: 56px;">
                </div>
            @endif
            <x-ui.field class="col-md-6" name="company_email" :label="__('Contact email')" type="email" :value="$settings['company_email'] ?? ''" />
            <x-ui.field class="col-md-6" name="company_phone" :label="__('Phone')" :value="$settings['company_phone'] ?? ''" />
            <x-ui.field class="col-12 mb-0" name="company_address" :label="__('Address')" type="textarea" rows="2"
                :value="$settings['company_address'] ?? ''" />
        </div>
    </div>

    <div class="ui-form-section">
        <h3 class="ui-form-section-title">{{ __('Tax identifiers') }}</h3>
        <div class="row">
            <x-ui.field class="col-md-6 mb-0" name="company_siret" :label="__('Registration number (SIRET / NIF)')"
                :value="$settings['company_siret'] ?? ''" />
            <x-ui.field class="col-md-6 mb-0" name="company_vat" :label="__('VAT number')" :value="$settings['company_vat'] ?? ''" />
        </div>
        {{-- TVA des ventes : taux figé sur chaque vente (SaleService), affiché sur la facture, le reçu et l'avoir --}}
        <div class="row mt-3">
            <div class="col-md-6 ui-field mb-md-0">
                {{-- Case décochée : le formulaire n'envoie rien, le champ caché envoie 0 --}}
                <input type="hidden" name="vat_enabled" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="vat_enabled" name="vat_enabled" value="1"
                        @checked(filter_var($settings['vat_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))>
                    <label class="form-check-label" for="vat_enabled">{{ __('VAT-registered: invoices show the VAT') }}</label>
                </div>
                <div class="form-text">{{ __('Selling prices include VAT. Each sale keeps the rate in force on its day; past sales do not change.') }}</div>
            </div>
            <x-ui.field class="col-md-6 mb-0" name="default_tax_rate" :label="__('VAT rate (%)')" type="number" step="0.01" min="0"
                max="100" :value="$settings['default_tax_rate'] ?? 20" />
        </div>
    </div>
</x-ui.card>
