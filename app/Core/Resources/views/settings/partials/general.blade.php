<x-ui.card title="Company" subtitle="Shown on invoices, receipts and purchase orders">
    <div class="ui-form-section">
        <div class="row">
            <x-ui.field class="col-md-6" name="company_name" label="Store / company name" :value="$settings['company_name'] ?? ''" />
            <x-ui.field class="col-md-6" name="company_logo" label="Logo" type="file" accept="image/*" />
            @if (!empty($settings['company_logo']))
                <div class="col-12 mb-3">
                    <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Current logo" class="border rounded p-1"
                        style="max-height: 56px;">
                </div>
            @endif
            <x-ui.field class="col-md-6" name="company_email" label="Contact email" type="email" :value="$settings['company_email'] ?? ''" />
            <x-ui.field class="col-md-6" name="company_phone" label="Phone" :value="$settings['company_phone'] ?? ''" />
            <x-ui.field class="col-12 mb-0" name="company_address" label="Address" type="textarea" rows="2"
                :value="$settings['company_address'] ?? ''" />
        </div>
    </div>

    <div class="ui-form-section">
        <h3 class="ui-form-section-title">Tax identifiers</h3>
        <div class="row">
            <x-ui.field class="col-md-6 mb-0" name="company_siret" label="Registration number (SIRET / NIF)"
                :value="$settings['company_siret'] ?? ''" />
            <x-ui.field class="col-md-6 mb-0" name="company_vat" label="VAT number" :value="$settings['company_vat'] ?? ''" />
        </div>
    </div>
</x-ui.card>
