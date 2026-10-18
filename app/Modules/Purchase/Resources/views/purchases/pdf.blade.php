{{--
    Bon de commande d'un achat (PurchaseController : aperçu et téléchargement, via App\Core\Exports\PdfDocument).
    Libellés : lang/xx/documents.php.
--}}
@extends('layouts.pdf')

@section('title', __('documents.purchase_order'))

@section('meta')
    <div><span>{{ __('documents.reference') }}</span> <strong>{{ $purchase->reference }}</strong></div>
    @if ($purchase->supplier_reference)
        <div><span>{{ __('documents.supplier_reference') }}</span> {{ $purchase->supplier_reference }}</div>
    @endif
    <div><span>{{ __('documents.date') }}</span> {{ $purchase->created_at->format('d/m/Y') }}</div>
    <div><span>{{ __('documents.status') }}</span> {{ __('documents.states.' . $purchase->state) }}</div>
@endsection

@section('content')
    <table class="pdf-parties">
        <tr>
            <td style="padding-right: 3mm;">
                <div class="pdf-box">
                    <div class="pdf-box-title">{{ __('documents.ordered_from') }}</div>
                    @if ($purchase->supplier)
                        <div class="pdf-box-name">{{ $purchase->supplier->name }}</div>
                        @foreach (['address', 'phone', 'email'] as $field)
                            @if ($purchase->supplier->{$field})
                                <div>{{ $purchase->supplier->{$field} }}</div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-muted">{{ __('documents.not_specified') }}</div>
                    @endif
                </div>
            </td>
            <td></td>
        </tr>
    </table>

    <table class="pdf-table">
        <thead>
            <tr>
                <th>{{ __('documents.item') }}</th>
                <th class="text-end" style="width: 10%;">{{ __('documents.quantity') }}</th>
                <th class="text-end" style="width: 19%;">{{ __('documents.unit_price') }}</th>
                <th class="text-end" style="width: 19%;">{{ __('documents.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                <tr @class(['is-even' => $loop->even])>
                    <td>
                        {{ $item->productColor->label }}
                        @if ($item->productColor->sku)
                            <div class="text-muted">{{ __('documents.sku') }} {{ $item->productColor->sku }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ money($item->unit_price) }}</td>
                    <td class="text-end">{{ money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="pdf-summary">
        <tr>
            <td class="pdf-summary-cell" style="width: 55%; padding-right: 6mm;">
                @if ($purchase->notes)
                    <div class="pdf-section-title">{{ __('documents.notes') }}</div>
                    <div class="text-prewrap">{{ $purchase->notes }}</div>
                @endif
            </td>
            <td class="pdf-summary-cell">
                <table class="pdf-totals">
                    @if ((float) $purchase->discount > 0)
                        <tr>
                            <td>{{ __('documents.subtotal') }}</td>
                            <td class="text-end">{{ money($purchase->total_amount) }}</td>
                        </tr>
                        <tr class="is-discount">
                            <td>{{ __('documents.discount') }}</td>
                            <td class="text-end">-{{ money($purchase->discount) }}</td>
                        </tr>
                    @endif
                    <tr class="is-grand">
                        <td>{{ __('documents.order_total') }}</td>
                        <td class="text-end">{{ money($purchase->total_net) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
