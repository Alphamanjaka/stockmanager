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
    </div>
</x-ui.card>
