@extends('layouts.app-back-office')

@section('title', __('Products'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="products" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">{{ __('Import products') }}</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New product') }}
    </a>
@endsection

@php
    $sortLink = fn (string $column) => request()->fullUrlWithQuery([
        'sort' => $column,
        'order' => request('sort') === $column && request('order') === 'asc' ? 'desc' : 'asc',
    ]);
    $sortIcon = fn (string $column) => request('sort') === $column
        ? (request('order') === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
        : 'bi-arrow-down-up text-subtle';
    $archived = request('status') === 'archived';
@endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <div class="ui-stats">
            @if ($mostSoldProduct && $mostSoldProduct->productColor)
                <x-ui.stat :label="__('Top selling')" :value="$mostSoldProduct->productColor->toString()" icon="bi-graph-up-arrow"
                    tone="success" :href="route('admin.products.show', $mostSoldProduct->productColor->product->id)"
                    :hint="__('Sold :count times', ['count' => $mostSoldProduct->total_quantity])" />
            @else
                <x-ui.stat :label="__('Top selling')" value="—" icon="bi-graph-up-arrow" tone="success" :hint="__('No sales recorded')" />
            @endif

            @if ($leastSoldProduct && $leastSoldProduct->productColor)
                <x-ui.stat :label="__('Least sold')" :value="$leastSoldProduct->productColor->toString()" icon="bi-graph-down-arrow"
                    tone="warning" :href="route('admin.products.show', $leastSoldProduct->productColor->product->id)"
                    :hint="__('Sold :count times', ['count' => $leastSoldProduct->total_quantity])" />
            @else
                <x-ui.stat :label="__('Least sold')" value="—" icon="bi-graph-down-arrow" tone="warning" :hint="__('No sales recorded')" />
            @endif
        </div>

        <x-ui.card flush :pagination="$productVariants" data-bulk>
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.products.index')">
                    <x-ui.search :placeholder="__('Name, SKU, category...')" />
                    <select name="category" class="form-select ui-filter" aria-label="{{ __('Category') }}">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected(request('category') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            {{-- Les cases portent le produit : cocher une variante coche tout le produit --}}
            <x-ui.bulk-bar export="products" :archive="route('admin.products.archive')" :restore="route('admin.products.restore')" />

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <x-ui.bulk-check-all />
                            <th>
                                <a href="{{ $sortLink('product') }}">{{ __('Product') }} <i class="bi {{ $sortIcon('product') }}" aria-hidden="true"></i></a>
                            </th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">
                                <a href="{{ $sortLink('stock') }}">{{ __('Stock') }} <i class="bi {{ $sortIcon('stock') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end">
                                <a href="{{ $sortLink('price_purchase') }}">{{ __('Cost price') }} <i class="bi {{ $sortIcon('price_purchase') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end">
                                <a href="{{ $sortLink('price') }}">{{ __('Price') }} <i class="bi {{ $sortIcon('price') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productVariants as $item)
                            <tr>
                                <x-ui.bulk-check :value="$item->product_id" :label="$item->toString()" />
                                <td>
                                    <a href="{{ route('admin.products.show', $item->product->id) }}" class="link-plain">{{ $item->toString() ?? __('N/A') }}</a>
                                    @if ($item->sku)
                                        <div class="ui-cell-meta">{{ $item->sku }}</div>
                                    @endif
                                </td>
                                <td><span class="ui-tag">{{ $item->getCategoryNameAttribute() ?? __('N/A') }}</span></td>
                                <td class="text-end">
                                    @if ($item->stock <= $item->alert_stock)
                                        <x-ui.badge tone="danger" dot :title="__('Low stock')">{{ $item->stock }}</x-ui.badge>
                                    @else
                                        {{ $item->stock }}
                                    @endif
                                </td>
                                <td class="text-end">{{ money($item->price_purchase) }}</td>
                                <td class="text-end fw-semibold">{{ money($item->price) }}</td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.products.show', $item->product_id)" icon="bi-eye">{{ __('View') }}</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.products.restore')" :ids="[$item->product_id]"
                                                icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.products.destroy', $item->product_id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" :confirm="__('Delete this product permanently?')"
                                                :confirm-text="__('Only possible if none of its variants was ever sold, purchased, counted or moved in stock.')">
                                                {{ __('Delete permanently') }}
                                            </x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.products.archive')" :ids="[$item->product_id]" icon="bi-archive"
                                                :confirm="__('Archive this product and all its variants?')"
                                                :confirm-text="__('It will no longer be offered at the checkout or in purchase orders. Its history is kept.')">
                                                {{ __('Archive') }}
                                            </x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="7" icon="bi-box">
                                {{ $archived ? __('No archived product.') : __('No product found.') }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
