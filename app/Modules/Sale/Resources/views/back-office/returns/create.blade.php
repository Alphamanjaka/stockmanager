@extends('layouts.app-back-office')

@section('title', 'Return items — ' . $sale->reference)
@section('subtitle', $sale->created_at->format('d/m/Y H:i') . ' · ' . ($sale->customer->name ?? 'Walk-in customer'))

@php
    $money = fn ($value) => money($value, 2, $sale->currency_symbol);
    // Remboursement d'une ligne : prix × (net payé / total brut), comme le calcule le service
    $netRatio = (float) $sale->total_brut > 0 ? (float) $sale->total_net / (float) $sale->total_brut : 0;
@endphp

@section('content')
    <x-ui.errors />

    <form action="{{ route('admin.sales.returns.store', $sale->id) }}" method="POST" class="ui-stack">
        @csrf

        <x-ui.alert tone="info" icon="bi-arrow-counterclockwise">
            The returned items go back to stock, and the refunded amount is deducted from the revenue of the day.
            The sale itself is kept unchanged. A credit note is numbered for each return.
        </x-ui.alert>

        <x-ui.card title="Items to return" flush>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Already returned</th>
                            <th class="text-end">Refund per item</th>
                            <th class="text-end" style="width: 140px">Quantity returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            @php $left = $item->quantity - ($returned[$item->id] ?? 0); @endphp
                            <tr @class(['text-muted' => $left <= 0])>
                                <td>
                                    <div class="ui-cell-title">{{ $item->productColor->label ?? '—' }}</div>
                                    <div class="ui-cell-meta">{{ $item->productColor->sku ?? '' }}</div>
                                </td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">{{ $returned[$item->id] ?? 0 }}</td>
                                <td class="text-end">{{ $money($item->unit_price * $netRatio) }}</td>
                                <td class="text-end">
                                    <input type="number" min="0" max="{{ max(0, $left) }}" step="1" name="quantities[{{ $item->id }}]"
                                        value="{{ old('quantities.' . $item->id, 0) }}" @disabled($left <= 0)
                                        class="form-control form-control-sm text-end" aria-label="Quantity returned">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card title="Refund">
            <div class="row">
                <x-ui.field class="col-md-8" name="reason" label="Reason" type="textarea" rows="2" required maxlength="500"
                    placeholder="e.g. Wrong size, defective item, cashier mistake..." :value="old('reason')" />
                <x-ui.field class="col-md-4" name="refund_method" label="Refunded by">
                    <select name="refund_method" id="refund_method" class="form-select">
                        @foreach (\App\Modules\Sale\Models\Sale::PAYMENT_METHODS as $method)
                            <option value="{{ $method }}" @selected(old('refund_method', $sale->payment_method) === $method)>{{ __('pos.methods.' . $method) }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>
            <p class="small text-muted mb-0">
                Net paid: {{ $money($sale->total_net) }} · already refunded: {{ $money($sale->refundedTotal()) }}.
                The discount of the sale is shared over its items.
            </p>

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-outline-secondary">Back to the sale</a>
                    <button type="submit" name="all" value="1" class="btn btn-outline-danger"
                        data-confirm="Cancel this sale?" data-confirm-tone="danger" data-confirm-ok="Cancel the sale"
                        data-confirm-text="Every item not returned yet goes back to stock and the remaining amount is refunded.">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Return everything (cancel the sale)
                    </button>
                    <button type="submit" class="btn btn-primary"
                        data-confirm="Record this return?" data-confirm-ok="Record"
                        data-confirm-text="The items go back to stock and a credit note is created. This cannot be undone.">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Record the return
                    </button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
