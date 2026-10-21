@extends('layouts.app-back-office')

@section('title', 'Sale ' . $sale->reference)
@section('subtitle', $sale->created_at->format('d/m/Y H:i') . ' · ' . ($sale->user->name ?? 'N/A'))

@section('actions')
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
                            <dd>{{ $sale->marginRate() ?? 0 }} % of the net total</dd>
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
