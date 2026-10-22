{{--
    Facture d'une vente (détail back office et front office).
    Les montants gardent la devise enregistrée avec la vente ($sale->currency_symbol), pas celle des paramètres actuels.
--}}
@php $amount = fn ($value) => number_format((float) $value, 2) . ' ' . $sale->currency_symbol; @endphp

<x-ui.card :title="__('Invoice')" flush>
    <x-slot:actions>
        <span class="ui-tag">{{ $sale->reference }}</span>
    </x-slot:actions>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="ui-stat-label mb-1">{{ __('Issuer') }}</div>
                <div class="fw-semibold">{{ $sale->company_name }}</div>
                @if ($sale->company_address)
                    <div class="text-muted">{{ $sale->company_address }}</div>
                @endif
                @if ($sale->company_phone)
                    <div class="text-muted">{{ __('Tel: :phone', ['phone' => $sale->company_phone]) }}</div>
                @endif
                @if ($sale->company_email)
                    <div class="text-muted">{{ __('Email: :email', ['email' => $sale->company_email]) }}</div>
                @endif
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="ui-stat-label mb-1">{{ __('Billed to') }}</div>
                @if ($sale->customer)
                    <div class="fw-semibold">{{ $sale->customer->name }}</div>
                    @if ($sale->customer->phone)
                        <div class="text-muted">{{ __('Tel: :phone', ['phone' => $sale->customer->phone]) }}</div>
                    @endif
                @else
                    <div class="text-muted">{{ __('Walk-in customer') }}</div>
                @endif
                <div class="text-muted mt-1">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive border-top">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Product') }}</th>
                    <th class="text-end">{{ __('Quantity') }}</th>
                    <th class="text-end">{{ $sale->hasVat() ? __('Unit price incl. VAT') : __('Unit price') }}</th>
                    <th class="text-end">{{ __('Subtotal') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->productColor->label }}</td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ $amount($item->unit_price) }}</td>
                        <td class="text-end">{{ $amount($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-slot:footer>
        <div class="ui-totals">
            <div><span class="text-muted">{{ __('Gross total') }}</span><span>{{ $amount($sale->total_brut) }}</span></div>
            <div>
                <span class="text-muted">{{ __('Discount') }}</span>
                <span @class(['text-danger' => $sale->discount > 0])>{{ $sale->discount > 0 ? '-' : '' }}{{ $amount($sale->discount) }}</span>
            </div>
            {{-- Prix TTC : total HT et TVA au taux figé sur la vente --}}
            @if ($sale->hasVat())
                <div><span class="text-muted">{{ __('Total excl. VAT') }}</span><span>{{ $amount($sale->excludingVat($sale->total_net)) }}</span></div>
                <div>
                    <span class="text-muted">{{ __('VAT :rate %', ['rate' => $sale->vatRateLabel()]) }}</span>
                    <span>{{ $amount($sale->vatIncluded($sale->total_net)) }}</span>
                </div>
            @endif
            <div class="ui-totals-grand">
                <span>{{ $sale->hasVat() ? __('Total incl. VAT') : __('Net total') }}</span><span>{{ $amount($sale->total_net) }}</span>
            </div>
        </div>
    </x-slot:footer>
</x-ui.card>
