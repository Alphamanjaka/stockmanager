@extends('layouts.app-back-office')

@section('title', 'Products')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="products" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">Import products</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New product
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
                <x-ui.stat label="Top selling" :value="$mostSoldProduct->productColor->toString()" icon="bi-graph-up-arrow"
                    tone="success" :href="route('admin.products.show', $mostSoldProduct->productColor->product->id)"
                    :hint="'Sold ' . $mostSoldProduct->total_quantity . ' times'" />
            @else
                <x-ui.stat label="Top selling" value="—" icon="bi-graph-up-arrow" tone="success" hint="No sales recorded" />
            @endif

            @if ($leastSoldProduct && $leastSoldProduct->productColor)
                <x-ui.stat label="Least sold" :value="$leastSoldProduct->productColor->toString()" icon="bi-graph-down-arrow"
                    tone="warning" :href="route('admin.products.show', $leastSoldProduct->productColor->product->id)"
                    :hint="'Sold ' . $leastSoldProduct->total_quantity . ' times'" />
            @else
                <x-ui.stat label="Least sold" value="—" icon="bi-graph-down-arrow" tone="warning" hint="No sales recorded" />
            @endif
        </div>

        <x-ui.card flush :pagination="$productVariants">
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.products.index')">
                    <x-ui.search placeholder="Name, SKU, category..." />
                    <select name="category" class="form-select ui-filter" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected(request('category') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ $sortLink('product') }}">Product <i class="bi {{ $sortIcon('product') }}" aria-hidden="true"></i></a>
                            </th>
                            <th>Category</th>
                            <th class="text-end">
                                <a href="{{ $sortLink('stock') }}">Stock <i class="bi {{ $sortIcon('stock') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end">
                                <a href="{{ $sortLink('price_purchase') }}">Cost price <i class="bi {{ $sortIcon('price_purchase') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end">
                                <a href="{{ $sortLink('price') }}">Price <i class="bi {{ $sortIcon('price') }}" aria-hidden="true"></i></a>
                            </th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productVariants as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.products.show', $item->product->id) }}" class="link-plain">{{ $item->toString() ?? 'N/A' }}</a>
                                    @if ($item->sku)
                                        <div class="ui-cell-meta">{{ $item->sku }}</div>
                                    @endif
                                </td>
                                <td><span class="ui-tag">{{ $item->getCategoryNameAttribute() ?? 'N/A' }}</span></td>
                                <td class="text-end">
                                    @if ($item->stock <= $item->alert_stock)
                                        <x-ui.badge tone="danger" dot title="Low stock">{{ $item->stock }}</x-ui.badge>
                                    @else
                                        {{ $item->stock }}
                                    @endif
                                </td>
                                <td class="text-end">{{ money($item->price_purchase) }}</td>
                                <td class="text-end fw-semibold">{{ money($item->price) }}</td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.products.show', $item->product_id)" icon="bi-eye">View</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.products.restore')" :ids="[$item->product_id]"
                                                icon="bi-arrow-counterclockwise">Restore</x-ui.menu-item>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.products.destroy', $item->product_id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" confirm="Delete this product permanently?"
                                                confirm-text="Only possible if none of its variants was ever sold, purchased, counted or moved in stock.">
                                                Delete permanently
                                            </x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.products.archive')" :ids="[$item->product_id]" icon="bi-archive"
                                                confirm="Archive this product and all its variants?"
                                                confirm-text="It will no longer be offered at the checkout or in purchase orders. Its history is kept.">
                                                Archive
                                            </x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" icon="bi-box">
                                {{ $archived ? 'No archived product.' : 'No product found.' }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
