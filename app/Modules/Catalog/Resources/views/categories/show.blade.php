@extends('layouts.app-back-office')

@section('title', $category->name)
@section('subtitle', $category->description ?: 'No description for this category.')

@section('actions')
    <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Products" :value="$category->products_count" icon="bi-box" tone="primary" />
            <x-ui.stat label="Estimated stock value" :value="money($stockValue)" icon="bi-cash-stack" tone="success" />
            <x-ui.stat label="Parent category" :value="$category->parent->name ?? 'Root'" icon="bi-diagram-3" tone="neutral"
                :href="$category->parent ? route('admin.categories.show', $category->parent->id) : null"
                :hint="'Created on ' . $category->created_at->format('d/m/Y')" />
        </div>

        <x-ui.card title="Products in this category" flush :pagination="$products">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><a href="{{ route('admin.products.show', $product->id) }}" class="link-plain">{{ $product->name }}</a></td>
                                <td class="text-end">{{ money($product->price) }}</td>
                                <td class="text-end">
                                    <x-ui.badge :tone="$product->quantity_stock <= $product->alert_stock ? 'danger' : 'success'">
                                        {{ $product->quantity_stock }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="4" icon="bi-box">No product in this category.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
