@extends('layouts.app-front-office')

@section('title', __('Product catalog'))

@section('content')
    <x-ui.card flush :pagination="$products">
        <x-slot:toolbar>
            <x-ui.filters :action="route('saler.products.index')">
                <x-ui.search :placeholder="__('Search by name or reference...')" />
            </x-ui.filters>
        </x-slot:toolbar>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th class="text-end">{{ __('Price') }}</th>
                        <th class="text-end">{{ __('Stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $item)
                        <tr>
                            <td>
                                <div class="ui-cell-title">{{ $item->toString() }}</div>
                                @if ($item->sku)
                                    <div class="ui-cell-meta">{{ $item->sku }}</div>
                                @endif
                            </td>
                            <td><span class="ui-tag">{{ $item->getCategoryNameAttribute() }}</span></td>
                            <td class="text-end fw-semibold">{{ money($item->price, 0) }}</td>
                            <td class="text-end">
                                <x-ui.badge :tone="$item->stock <= $item->alert_stock ? 'danger' : 'success'" dot>{{ $item->stock }}</x-ui.badge>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="4" icon="bi-box">{{ __('No product found.') }}</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
