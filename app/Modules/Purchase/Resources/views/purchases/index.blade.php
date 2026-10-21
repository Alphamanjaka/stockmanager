@extends('layouts.app-back-office')

@section('title', __('Purchases'))

@section('actions')
    {{-- Les liens d'export suivent les filtres du tableau (data-export-link, mis à jour par purchases-index.js) --}}
    <x-ui.actions-menu>
        <x-ui.menu-item :href="route('admin.purchases.createFromShortage')" icon="bi-exclamation-triangle">{{ __('Restock shortages') }}</x-ui.menu-item>
        <li><hr class="dropdown-divider"></li>
        <x-ui.export-items list="purchases" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">{{ __('Import purchases') }}</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New purchase') }}
    </a>
@endsection

@php
    $states = [
        'All' => ['label' => __('All'), 'icon' => 'bi-list-ul'],
        'Draft' => ['label' => __('Drafts'), 'icon' => 'bi-pencil'],
        'Ordered' => ['label' => __('Ordered'), 'icon' => 'bi-send'],
        'Received' => ['label' => __('Received'), 'icon' => 'bi-check2-circle'],
    ];
@endphp

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat :label="__('Total purchases (net)')" :value="money($totalSpent)" icon="bi-cash-stack" tone="primary" />
            <x-ui.stat :label="__('Discounts obtained')" :value="money($totalDiscounts)" icon="bi-percent" tone="warning" />
            <x-ui.stat :label="__('Number of purchases')" :value="$totalPurchases" icon="bi-receipt" tone="success" />
            <x-ui.stat :label="__('Average purchase')" :value="money($averagePurchaseValue)" icon="bi-calculator" tone="info" />
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

    {{-- Textes du tableau (resources/js/purchases-index.js), dans la langue de l'application.
         Construits à part : la directive json découpe son argument aux virgules. --}}
    @php
        $i18n = [
            'states' => ['Draft' => __('Draft'), 'Ordered' => __('Ordered'), 'Received' => __('Received')],
            'unexpectedError' => __('An unexpected error occurred.'),
            'receiveTitle' => __('Mark this purchase as received?'),
            'receiveText' => __('The stock of every line will be increased. This cannot be undone.'),
            'receiveConfirm' => __('Mark as received'),
            'deleteTitle' => __('Delete this purchase?'),
            'deleteText' => __('This action cannot be undone.'),
            'delete' => __('Delete'),
            'edit' => __('Edit'),
            'view' => __('View'),
            'received' => __('Received'),
            'noPurchase' => __('No purchase found'),
            'reference' => __('Reference'),
            'supplier' => __('Supplier'),
            'date' => __('Date'),
            'status' => __('Status'),
            'netTotal' => __('Net total'),
        ];
    @endphp
    <script type="application/json" id="purchases-i18n">@json($i18n)</script>
@endsection

@push('scripts')
    @vite('resources/js/purchases-index.js')
@endpush
