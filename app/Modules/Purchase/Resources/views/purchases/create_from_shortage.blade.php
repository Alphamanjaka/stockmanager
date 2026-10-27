@extends('layouts.app-back-office')

@section('title', __('Restock shortages'))
@section('subtitle', __('Suggested orders for variants that will reach their alert threshold before a new delivery, given their sales speed and the supplier lead time, grouped by last supplier.'))

@section('content')
    @if ($groupedProducts->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="bi-check2-circle" :title="__('Good news!')">{{ __('No product requires restocking at the moment.') }}</x-ui.empty>
        </x-ui.card>
    @else
        <form action="{{ route('admin.purchases.storeFromShortage') }}" method="POST">
            @csrf
            <x-ui.alert tone="info" class="mb-3">
                {{ __('Uncheck the products you do not wish to order and adjust the quantities before creating the orders.') }}
            </x-ui.alert>

            <div class="ui-stack">
                @foreach ($groupedProducts as $group)
                    <x-ui.card :title="$group['supplier_name']" icon="bi-truck" flush
                        :subtitle="$group['supplier_id'] ? __(':count product(s) to restock', ['count' => count($group['products'])]) : __('No supplier found in the purchase history: create this order manually.')">
                        @unless ($group['supplier_id'])
                            <x-slot:actions>
                                <x-ui.badge tone="warning" icon="bi-exclamation-triangle">{{ __('Action required') }}</x-ui.badge>
                            </x-slot:actions>
                        @endunless

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 48px"><span class="visually-hidden">{{ __('Order') }}</span></th>
                                        <th>{{ __('Product') }}</th>
                                        <th class="text-end">{{ __('Current stock') }}</th>
                                        <th class="text-end">{{ __('On order') }}</th>
                                        <th class="text-end">{{ __('Sales / day') }}</th>
                                        <th class="text-end">{{ __('Days of stock') }}</th>
                                        <th class="text-end">{{ __('Unit cost') }}</th>
                                        <th style="width: 150px">{{ __('Quantity to order') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['products'] as $product)
                                        <tr>
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="items[{{ $product->id }}][selected]" value="1"
                                                    checked aria-label="{{ __('Order :product', ['product' => $product->label]) }}">
                                                {{-- Toujours envoyés, pour éviter les « Undefined array key » à la soumission --}}
                                                <input type="hidden" name="items[{{ $product->id }}][supplier_id]" value="{{ $group['supplier_id'] }}">
                                                <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                                <input type="hidden" name="items[{{ $product->id }}][unit_price]" value="{{ $product->last_unit_price ?? 0 }}">
                                            </td>
                                            <td>
                                                <div class="ui-cell-title">{{ $product->label }}</div>
                                                <div class="ui-cell-meta">{{ $product->sku }}</div>
                                            </td>
                                            <td class="text-end">
                                                <x-ui.badge :tone="$product->stock <= $product->alert_stock ? 'danger' : 'warning'" dot>{{ $product->stock }}</x-ui.badge>
                                                <div class="ui-cell-meta">{{ __('Threshold: :count', ['count' => $product->alert_stock]) }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if ($product->ordered_quantity > 0)
                                                    {{ $product->ordered_quantity }}
                                                    <div class="ui-cell-meta">{{ __('Already ordered, not received') }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($product->sold_quantity > 0)
                                                    {{ number_format($product->daily_sales, 1) }}
                                                    <div class="ui-cell-meta">{{ __(':count sold in :days days', ['count' => $product->sold_quantity, 'days' => $product->sales_period]) }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                    <div class="ui-cell-meta">{{ __('No sale in :days days', ['days' => $product->sales_period]) }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($product->days_left !== null)
                                                    <x-ui.badge :tone="$product->days_left < $product->lead_time_days ? 'danger' : 'warning'">
                                                        {{ __(':count d', ['count' => $product->days_left]) }}
                                                    </x-ui.badge>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                                <div class="ui-cell-meta">{{ __('Delivery in :count d', ['count' => $product->lead_time_days]) }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if ($product->last_unit_price !== null)
                                                    {{ money($product->last_unit_price) }}
                                                    <div class="ui-cell-meta">{{ $product->cost_source === 'catalog' ? __('Purchase price (product)') : __('Last purchase') }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                    <div class="ui-cell-meta">{{ __('To fill in on the order') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end" name="items[{{ $product->id }}][quantity]"
                                                    value="{{ $product->suggested_quantity }}" min="1" aria-label="{{ __('Quantity to order') }}">
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
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Create selected orders') }}
                </button>
            </div>
        </form>
    @endif
@endsection
