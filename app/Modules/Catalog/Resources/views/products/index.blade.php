@extends('layouts.app-back-office')

@section('title', 'Products')

@section('actions')
    <a href="{{ route('admin.products.exportPdf') }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF
    </a>
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
@endphp

@section('content')
    <div class="ui-stack">
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
                    <x-ui.search placeholder="Name, reference..." />
                    <select name="category" class="form-select ui-filter" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </x-ui.filters>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ $sortLink('product_id') }}">Product <i class="bi {{ $sortIcon('product_id') }}" aria-hidden="true"></i></a>
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
                                    <div class="ui-row-actions">
                                        <a href="{{ route('admin.products.show', $item->product->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <x-ui.delete-button :action="route('admin.products.destroy', $item->product->id)"
                                            confirm="Delete this product and all its variants?" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" icon="bi-box">No product found.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
