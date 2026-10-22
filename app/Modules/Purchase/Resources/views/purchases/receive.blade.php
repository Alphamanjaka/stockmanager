@extends('layouts.app-back-office')

{{-- Livraison d'un achat : quantité reçue par ligne, proposée égale au reste à livrer (PurchaseService::receive) --}}

@section('title', __('Delivery — :reference', ['reference' => $purchase->reference]))
@section('subtitle', ($purchase->supplier->name ?? __('documents.not_specified')) . ' · ' . $purchase->stateLabel())

@section('content')
    <x-ui.errors />

    <form action="{{ route('admin.purchases.receive.store', $purchase->id) }}" method="POST" class="ui-stack">
        @csrf

        <x-ui.alert tone="info" icon="bi-truck">
            {{ __('Enter the quantity actually delivered for each line. The stock increases by these quantities, at the cost of the purchase. What is not delivered stays expected: the purchase becomes partially received until the rest arrives.') }}
        </x-ui.alert>

        <x-ui.card :title="__('Delivered items')" flush>
            <x-slot:actions>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-delivery-fill="remaining">
                    <i class="bi bi-check2-all" aria-hidden="true"></i> {{ __('Everything that remains') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-delivery-fill="zero">
                    <i class="bi bi-eraser" aria-hidden="true"></i> {{ __('Set all to 0') }}
                </button>
            </x-slot:actions>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Ordered') }}</th>
                            <th class="text-end">{{ __('Already received') }}</th>
                            <th class="text-end">{{ __('To deliver') }}</th>
                            <th class="text-end" style="width: 160px">{{ __('Delivered now') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            @php $left = $item->remainingQuantity(); @endphp
                            <tr @class(['text-muted' => $left === 0])>
                                <td>
                                    <div class="ui-cell-title">{{ $item->productColor->label ?? '—' }}</div>
                                    <div class="ui-cell-meta">{{ $item->productColor->sku ?? '' }}</div>
                                </td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">{{ $item->received_quantity }}</td>
                                <td class="text-end">{{ $left }}</td>
                                <td class="text-end">
                                    <input type="number" min="0" max="{{ $left }}" step="1" name="quantities[{{ $item->id }}]"
                                        value="{{ old('quantities.' . $item->id, $left) }}" data-remaining="{{ $left }}" @disabled($left === 0)
                                        class="form-control form-control-sm text-end" aria-label="{{ __('Quantity delivered: :product', ['product' => $item->productColor->label ?? '']) }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="ui-form-bar ui-form-actions">
            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary"
                data-confirm="{{ __('Record this delivery?') }}" data-confirm-ok="{{ __('Record') }}"
                data-confirm-text="{{ __('The delivered quantities are added to the stock. This cannot be undone.') }}">
                <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Record the delivery') }}
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // « Tout ce qui reste » / « Tout à 0 » : remplit les quantités livrées de toutes les lignes
        document.querySelectorAll('[data-delivery-fill]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('input[data-remaining]:not(:disabled)').forEach(function (input) {
                    input.value = button.dataset.deliveryFill === 'zero' ? 0 : input.dataset.remaining;
                });
            });
        });
    </script>
@endpush
