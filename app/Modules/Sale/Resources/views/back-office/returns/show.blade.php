@extends('layouts.app-back-office')

@section('title', __('Credit note :reference', ['reference' => $saleReturn->reference]))
@section('subtitle', $saleReturn->created_at->format('d/m/Y H:i') . ' · ' . ($saleReturn->user->name ?? __('N/A')))

@php
    $sale = $saleReturn->sale;
    $money = fn ($value) => money($value, 2, $sale->currency_symbol);
@endphp

@section('actions')
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer" aria-hidden="true"></i> {{ __('Print') }}
    </button>
    <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-receipt" aria-hidden="true"></i> {{ __('Sale :reference', ['reference' => $sale->reference]) }}
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card :title="__('Returned items')" flush>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Unit price') }}</th>
                                <th class="text-end">{{ __('Refunded') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($saleReturn->items as $item)
                                <tr>
                                    <td>
                                        <div class="ui-cell-title">{{ $item->productColor->label ?? '—' }}</div>
                                        <div class="ui-cell-meta">{{ $item->productColor->sku ?? '' }}</div>
                                    </td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ $money($item->unit_price) }}</td>
                                    <td class="text-end">{{ $money($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ui-totals">
                    <div class="ui-totals-grand"><span>{{ __('Total refunded') }}</span><span>{{ $money($saleReturn->total) }}</span></div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card :title="__('Credit note')">
                <dl class="ui-dl">
                    <dt>{{ __('Sale') }}</dt>
                    <dd><a href="{{ route('admin.sales.show', $sale->id) }}">{{ $sale->reference }}</a> · {{ $sale->created_at->format('d/m/Y') }}</dd>
                    <dt>{{ __('Customer') }}</dt>
                    <dd>{{ $sale->customer->name ?? __('Walk-in customer') }}</dd>
                    <dt>{{ __('Reason') }}</dt>
                    <dd class="text-prewrap">{{ $saleReturn->reason }}</dd>
                    <dt>{{ __('Refunded by') }}</dt>
                    <dd>{{ $saleReturn->refund_method ? __('pos.methods.' . $saleReturn->refund_method) : '—' }}</dd>
                    <dt>{{ __('Recorded by') }}</dt>
                    <dd>{{ $saleReturn->user->name ?? '—' }}</dd>
                </dl>
            </x-ui.card>
        </div>
    </div>
@endsection
