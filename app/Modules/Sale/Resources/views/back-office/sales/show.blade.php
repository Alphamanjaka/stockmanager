@extends('layouts.app-back-office')

@section('title', 'Sale ' . $sale->reference)
@section('subtitle', $sale->created_at->format('d/m/Y H:i') . ' · ' . ($sale->user->name ?? 'N/A'))

@php $status = $sale->returnStatus(); @endphp

@section('actions')
    @if ($status !== 'returned')
        <a href="{{ route('admin.sales.returns.create', $sale->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Return items
        </a>
    @endif
    <a href="{{ route('admin.sales.receipt', ['id' => $sale->id, 'print' => 1]) }}" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-printer" aria-hidden="true"></i> {{ __('pos.print_receipt') }}
    </a>
    <a href="{{ route('admin.sales.pdf', $sale->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            @include('sale::partials.invoice')
        </div>

        <div class="col-lg-4">
            <div class="ui-stack">
                {{-- Avoirs : la facture reste inchangée, les retours la complètent --}}
                @if ($status)
                    <x-ui.card title="Returns" flush>
                        <x-slot:actions>
                            <x-ui.badge :tone="$status === 'returned' ? 'danger' : 'warning'">
                                {{ $status === 'returned' ? 'Sale cancelled' : 'Partially returned' }}
                            </x-ui.badge>
                        </x-slot:actions>
                        <ul class="list-group list-group-flush">
                            @foreach ($sale->returns as $saleReturn)
                                <li class="list-group-item d-flex justify-content-between gap-2">
                                    <span>
                                        <a href="{{ route('admin.sale-returns.show', $saleReturn->id) }}">{{ $saleReturn->reference }}</a>
                                        <span class="ui-cell-meta d-block">
                                            {{ $saleReturn->created_at->format('d/m/Y H:i') }} · {{ $saleReturn->user->name ?? '—' }} · {{ $saleReturn->items->sum('quantity') }} item(s)
                                        </span>
                                    </span>
                                    <span class="text-danger text-nowrap">-{{ money($saleReturn->total, 2, $sale->currency_symbol) }}</span>
                                </li>
                            @endforeach
                            <li class="list-group-item d-flex justify-content-between fw-semibold">
                                <span>Net after returns</span>
                                <span>{{ money($sale->netAfterReturns(), 2, $sale->currency_symbol) }}</span>
                            </li>
                        </ul>
                    </x-ui.card>
                @endif

                {{-- Coûts figés à la vente : back office seulement (la facture est partagée avec les vendeurs) --}}
                <x-ui.card title="Margin">
                    @if ($sale->grossMargin() === null)
                        <span class="text-muted">The purchase cost of at least one item was not known at the time of the sale.</span>
                    @else
                        <dl class="ui-dl">
                            <dt>Cost of items</dt>
                            <dd>{{ money($sale->costTotal(), 2, $sale->currency_symbol) }}</dd>
                            <dt>Gross margin</dt>
                            <dd @class(['fw-semibold', 'text-danger' => $sale->grossMargin() < 0])>{{ money($sale->grossMargin(), 2, $sale->currency_symbol) }}</dd>
                            <dt>Margin rate</dt>
                            <dd>{{ $sale->marginRate() ?? 0 }} % of the net total{{ $status ? ', returns deducted' : '' }}</dd>
                        </dl>
                    @endif
                </x-ui.card>

                <x-ui.card title="Seller">
                    <dl class="ui-dl">
                        <dt>Name</dt>
                        <dd>{{ $sale->user->name ?? 'N/A' }}</dd>
                        <dt>Email</dt>
                        <dd>{{ $sale->user->email ?? '—' }}</dd>
                        <dt>Phone</dt>
                        <dd>{{ $sale->user->phone ?? '—' }}</dd>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Customer">
                    @if ($sale->customer)
                        <dl class="ui-dl">
                            <dt>Name</dt>
                            <dd><a href="{{ route('admin.customers.show', $sale->customer->id) }}">{{ $sale->customer->name }}</a></dd>
                            <dt>Phone</dt>
                            <dd>{{ $sale->customer->phone ?: '—' }}</dd>
                        </dl>
                    @else
                        <span class="text-muted">Walk-in customer</span>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
