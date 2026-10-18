@extends('layouts.app-back-office')

@section('title', 'Purchases')

@section('actions')
    {{-- Les liens d'export suivent les filtres du tableau (data-export-link, mis à jour par purchases-index.js) --}}
    <x-ui.actions-menu>
        <x-ui.menu-item :href="route('admin.purchases.createFromShortage')" icon="bi-exclamation-triangle">Restock shortages</x-ui.menu-item>
        <li><hr class="dropdown-divider"></li>
        <x-ui.export-items list="purchases" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">Import purchases</x-ui.menu-item>
    </x-ui.actions-menu>
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

        {{-- Sélection pilotée par Tabulator (purchases-index.js) : data-bulk-manual --}}
        <x-ui.card flush data-bulk data-bulk-manual>
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

            <x-ui.bulk-bar export="purchases" />

            <div id="purchases-table" data-url="{{ route('admin.purchases.get-purchases-api') }}"></div>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/purchases-index.js')
@endpush
