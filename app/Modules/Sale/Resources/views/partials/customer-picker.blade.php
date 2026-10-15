{{-- Client facultatif de la vente : client existant, ou nouveau client créé avec la vente --}}
<div class="mb-3">
    <label class="visually-hidden" for="customer-select">{{ __('pos.customer') }}</label>
    <select name="customer_id" id="customer-select" class="form-select">
        <option value="">{{ __('pos.walk_in') }}</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                {{ $customer->name }}{{ $customer->phone ? ' — ' . $customer->phone : '' }}
            </option>
        @endforeach
    </select>
    <a class="small d-inline-block mt-1" data-bs-toggle="collapse" href="#new-customer" role="button"
        aria-expanded="false" aria-controls="new-customer">{{ __('pos.new_customer') }}</a>
    <div class="collapse {{ old('new_customer_name') ? 'show' : '' }}" id="new-customer">
        <input type="text" name="new_customer_name" class="form-control form-control-sm mt-2"
            placeholder="{{ __('pos.customer_name') }}" aria-label="{{ __('pos.customer_name') }}"
            value="{{ old('new_customer_name') }}" maxlength="255" autocomplete="off">
        <input type="text" name="new_customer_phone" class="form-control form-control-sm mt-2"
            placeholder="{{ __('pos.customer_phone') }}" aria-label="{{ __('pos.customer_phone') }}"
            value="{{ old('new_customer_phone') }}" maxlength="50" autocomplete="off">
        <div class="form-text">{{ __('pos.new_customer_hint') }}</div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && jQuery.fn.select2) {
                const select = jQuery('#customer-select');
                // Dans une fenêtre modale, la liste doit y être rattachée pour que sa recherche reçoive le clavier
                const modal = select.closest('.modal');
                select.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: modal.length ? modal : jQuery(document.body),
                });
            }
        });
    </script>
@endpush
