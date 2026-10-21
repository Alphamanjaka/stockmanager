{{--
    Facture d'une vente (SaleAdminController et SaleController::exportPdf, via App\Core\Exports\PdfDocument).
    Coordonnées de l'entreprise et devise figées à la vente (sales.company_*, sales.currency_symbol), comme le reçu :
    la facture reste juste si les Paramètres changent ensuite. Libellés : lang/xx/documents.php.
--}}
@extends('layouts.pdf', ['company' => [
    'name' => $sale->company_name,
    'address' => $sale->company_address,
    'phone' => $sale->company_phone,
    'email' => $sale->company_email,
]])

@php $money = fn ($amount) => money($amount, 2, $sale->currency_symbol); @endphp

@section('title', __('documents.invoice'))

@section('meta')
    <div><span>{{ __('documents.reference') }}</span> <strong>{{ $sale->reference }}</strong></div>
    <div><span>{{ __('documents.date') }}</span> {{ $sale->created_at->format('d/m/Y H:i') }}</div>
@endsection

@section('content')
    <table class="pdf-parties">
        <tr>
            <td style="padding-right: 3mm;">
                @if ($sale->customer)
                    <div class="pdf-box">
                        <div class="pdf-box-title">{{ __('documents.billed_to') }}</div>
                        <div class="pdf-box-name">{{ $sale->customer->name }}</div>
                        @foreach (['address', 'phone', 'email'] as $field)
                            @if ($sale->customer->{$field})
                                <div>{{ $sale->customer->{$field} }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </td>
            <td style="padding-left: 3mm;">
                @if ($sale->user)
                    <div class="pdf-box">
                        <div class="pdf-box-title">{{ __('documents.seller') }}</div>
                        <div class="pdf-box-name">{{ $sale->user->name }}</div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="pdf-table">
        <thead>
            <tr>
                <th>{{ __('documents.item') }}</th>
                <th class="text-end" style="width: 10%;">{{ __('documents.quantity') }}</th>
                <th class="text-end" style="width: 19%;">{{ $sale->hasVat() ? __('documents.unit_price_incl_vat') : __('documents.unit_price') }}</th>
                <th class="text-end" style="width: 19%;">{{ __('documents.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr @class(['is-even' => $loop->even])>
                    <td>
                        {{ $item->productColor->label }}
                        @if ($item->productColor->sku)
                            <div class="text-muted">{{ __('documents.sku') }} {{ $item->productColor->sku }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ $money($item->unit_price) }}</td>
                    <td class="text-end">{{ $money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="pdf-summary">
        <tr>
            <td class="pdf-summary-cell" style="width: 55%; padding-right: 6mm;">
                <div><span class="text-muted">{{ __('documents.paid_by') }}</span> {{ __('pos.methods.' . $sale->payment_method) }}</div>
                @if ($sale->amount_received !== null && $sale->payment_method === \App\Modules\Sale\Models\Sale::PAYMENT_CASH)
                    <div><span class="text-muted">{{ __('documents.received') }}</span> {{ $money($sale->amount_received) }}</div>
                    <div><span class="text-muted">{{ __('documents.change') }}</span> {{ $money($sale->change_due) }}</div>
                @endif
            </td>
            <td class="pdf-summary-cell">
                <table class="pdf-totals">
                    @if ((float) $sale->discount > 0)
                        <tr>
                            <td>{{ __('documents.subtotal') }}</td>
                            <td class="text-end">{{ $money($sale->total_brut) }}</td>
                        </tr>
                        <tr class="is-discount">
                            <td>{{ __('documents.discount') }}</td>
                            <td class="text-end">-{{ $money($sale->discount) }}</td>
                        </tr>
                    @endif
                    {{-- Prix TTC : le total HT et la TVA sont déduits du net à payer, au taux figé sur la vente --}}
                    @if ($sale->hasVat())
                        <tr>
                            <td>{{ __('documents.total_excl_vat') }}</td>
                            <td class="text-end">{{ $money($sale->excludingVat($sale->total_net)) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('documents.vat', ['rate' => $sale->vatRateLabel()]) }}</td>
                            <td class="text-end">{{ $money($sale->vatIncluded($sale->total_net)) }}</td>
                        </tr>
                    @endif
                    <tr class="is-grand">
                        <td>{{ $sale->hasVat() ? __('documents.total_due_incl_vat') : __('documents.total_due') }}</td>
                        <td class="text-end">{{ $money($sale->total_net) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="pdf-section text-muted">{{ __('documents.thanks') }}</div>
@endsection
