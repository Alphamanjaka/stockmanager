{{-- Client facultatif de la vente : client existant, ou nouveau client créé avec la vente --}}
<div class="mb-3">
    <label class="form-label small" for="customer-select">Customer (optional)</label>
    <select name="customer_id" id="customer-select" class="form-select">
        <option value="">— Walk-in customer —</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                {{ $customer->name }}{{ $customer->phone ? ' — ' . $customer->phone : '' }}
            </option>
        @endforeach
    </select>
    <a class="small d-inline-block mt-1" data-bs-toggle="collapse" href="#new-customer" role="button"
        aria-expanded="false" aria-controls="new-customer">+ New customer</a>
    <div class="collapse {{ old('new_customer_name') ? 'show' : '' }}" id="new-customer">
        <input type="text" name="new_customer_name" class="form-control form-control-sm mt-2" placeholder="Name"
            value="{{ old('new_customer_name') }}" maxlength="255">
        <input type="text" name="new_customer_phone" class="form-control form-control-sm mt-2" placeholder="Phone"
            value="{{ old('new_customer_phone') }}" maxlength="50">
        <div class="form-text">Used only if no existing customer is selected.</div>
    </div>
</div>
<hr>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && jQuery.fn.select2) {
                jQuery('#customer-select').select2({ theme: 'bootstrap-5', width: '100%' });
            }
        });
    </script>
@endpush
