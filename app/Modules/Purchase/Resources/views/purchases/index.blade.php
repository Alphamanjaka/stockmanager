@extends('layouts.app-back-office')

@section('title', 'Purchases')

@section('actions')
    <a href="{{ route('admin.purchases.createFromShortage') }}" class="btn btn-outline-secondary">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Restock shortages
    </a>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New purchase
    </a>
@endsection

@php
    $states = [
        'All' => ['label' => 'All', 'icon' => 'bi-list-ul'],
        'Draft' => ['label' => 'Drafts', 'icon' => 'bi-pencil'],
        'Ordered' => ['label' => 'Ordered', 'icon' => 'bi-send'],
        'Received' => ['label' => 'Received', 'icon' => 'bi-check2-circle'],
    ];
@endphp

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Total purchases (net)" :value="money($totalSpent)" icon="bi-cash-stack" tone="primary" />
            <x-ui.stat label="Discounts obtained" :value="money($totalDiscounts)" icon="bi-percent" tone="warning" />
            <x-ui.stat label="Number of purchases" :value="$totalPurchases" icon="bi-receipt" tone="success" />
            <x-ui.stat label="Average purchase" :value="money($averagePurchaseValue)" icon="bi-calculator" tone="info" />
        </div>

        <x-ui.card flush>
            <x-slot:toolbar>
                {{-- Filtre par statut : appliqué au tableau par purchases-index.js --}}
                <ul class="nav nav-pills" id="state-tabs" role="tablist">
                    @foreach ($states as $stateKey => $details)
                        <li class="nav-item" role="presentation">
                            <button @class(['nav-link', 'active' => $loop->first]) data-bs-toggle="pill" type="button" role="tab"
                                data-state="{{ $stateKey === 'All' ? '' : $stateKey }}">
                                <i class="bi {{ $details['icon'] }}" aria-hidden="true"></i>
                                {{ $details['label'] }}
                                <span class="ui-tag">{{ $stateCounts[$stateKey] ?? 0 }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </x-slot:toolbar>

            <div id="purchases-table" data-url="{{ route('admin.purchases.get-purchases-api') }}"></div>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/purchases-index.js')
@endpush
