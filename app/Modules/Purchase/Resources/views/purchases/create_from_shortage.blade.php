@extends('layouts.app-back-office')

@section('title', 'Restock shortages')
@section('subtitle', 'Suggested orders for variants at or below their alert threshold, grouped by last supplier.')

@section('content')
    @if ($groupedProducts->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="bi-check2-circle" title="Good news!">No product requires restocking at the moment.</x-ui.empty>
        </x-ui.card>
    @else
        <form action="{{ route('admin.purchases.storeFromShortage') }}" method="POST">
            @csrf
            <x-ui.alert tone="info" class="mb-3">
                Uncheck the products you do not wish to order and adjust the quantities before creating the orders.
            </x-ui.alert>

            <div class="ui-stack">
                @foreach ($groupedProducts as $group)
                    <x-ui.card :title="$group['supplier_name']" icon="bi-truck" flush
                        :subtitle="$group['supplier_id'] ? count($group['products']) . ' product(s) to restock' : 'No supplier found in the purchase history: create this order manually.'">
                        @unless ($group['supplier_id'])
                            <x-slot:actions>
                                <x-ui.badge tone="warning" icon="bi-exclamation-triangle">Action required</x-ui.badge>
                            </x-slot:actions>
                        @endunless

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 48px"><span class="visually-hidden">Order</span></th>
                                        <th>Product</th>
                                        <th class="text-end">Current stock</th>
                                        <th class="text-end">Alert threshold</th>
                                        <th class="text-end">Last unit cost</th>
                                        <th style="width: 150px">Quantity to order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['products'] as $product)
                                        <tr>
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="items[{{ $product->id }}][selected]" value="1"
                                                    checked aria-label="Order {{ $product->label }}">
                                                {{-- Toujours envoyés, pour éviter les « Undefined array key » à la soumission --}}
                                                <input type="hidden" name="items[{{ $product->id }}][supplier_id]" value="{{ $group['supplier_id'] }}">
                                                <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                                <input type="hidden" name="items[{{ $product->id }}][unit_price]" value="{{ $product->last_unit_price }}">
                                            </td>
                                            <td>
                                                <div class="ui-cell-title">{{ $product->label }}</div>
                                                <div class="ui-cell-meta">{{ $product->sku }}</div>
                                            </td>
                                            <td class="text-end"><x-ui.badge tone="danger" dot>{{ $product->stock }}</x-ui.badge></td>
                                            <td class="text-end text-muted">{{ $product->alert_stock }}</td>
                                            <td class="text-end">{{ money($product->last_unit_price) }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end" name="items[{{ $product->id }}][quantity]"
                                                    value="{{ $product->suggested_quantity }}" min="1" aria-label="Quantity to order">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="ui-form-bar ui-form-actions">
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Create selected orders
                </button>
            </div>
        </form>
    @endif
@endsection
