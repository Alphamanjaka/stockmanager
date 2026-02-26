@extends('layouts.app-back-office')

@section('title', 'Achats')

@section('content')
    <style>
        /* Tabulator Customization */
        .tabulator {
            font-size: 0.8rem;
            border: none;
        }

        .tabulator-row .tabulator-cell {
            padding: 6px 10px;
            vertical-align: middle;
        }

        .tabulator .tabulator-header .tabulator-col {
            background-color: #f8f9fc;
            border-color: #e3e6f0;
        }
    </style>

    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex gap-2">
                <a href="{{ route('admin.purchases.createFromShortage') }}" class="btn btn-warning">
                    <i class="fas fa-exclamation-triangle"></i> Commande par rupture
                </a>
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvel Achat
                </a>
            </div>
        </div>
        <!-- Nav Tabs -->
        <ul class="nav nav-tabs" id="purchaseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-pane"
                    type="button" role="tab" aria-controls="list-pane" aria-selected="true">
                    <i class="fas fa-list me-1"></i> Liste des Achats
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-pane" type="button"
                    role="tab" aria-controls="stats-pane" aria-selected="false">
                    <i class="fas fa-chart-line me-1"></i> Statistiques
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="purchaseTabsContent">
            {{-- Tab 1: Liste des Achats --}}
            <div class="tab-pane fade show active" id="list-pane" role="tabpanel" aria-labelledby="list-tab" tabindex="0">
                <div class="pt-3">
                    {{-- State Tabs --}}
                    <ul class="nav nav-pills mb-3" id="state-tabs" role="tablist">
                        @php
                            $states = [
                                'All' => ['label' => 'Tous', 'badge' => 'light text-dark'],
                                'Draft' => ['label' => 'Brouillons', 'badge' => 'secondary'],
                                'Ordered' => ['label' => 'Commandés', 'badge' => 'info'],
                                'Received' => ['label' => 'Reçus', 'badge' => 'success'],
                                'Paid' => ['label' => 'Payés', 'badge' => 'primary'],
                            ];
                        @endphp
                        @foreach ($states as $stateKey => $details)
                            @php
                                $count = $stateCounts[$stateKey] ?? 0;
                            @endphp
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }} position-relative"
                                    data-bs-toggle="pill" type="button" role="tab"
                                    data-state="{{ $stateKey === 'All' ? '' : $stateKey }}">
                                    {{ $details['label'] }}
                                    <span
                                        class="badge rounded-pill bg-{{ $details['badge'] }} ms-1">{{ $count }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Table --}}
                    <div class="card shadow">
                        <div class="card-body p-2">
                            {{-- Tabulator Container --}}
                            <div id="purchases-table" data-url="{{ route('admin.purchases.get-purchases-api') }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Statistiques --}}
            <div class="tab-pane fade" id="stats-pane" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">
                <div class="py-4">
                    {{-- KPI Section --}}
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-start border-info border-4 shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total
                                                Achats (Net)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($totalSpent, 2) }} Mga
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Remises
                                                Obtenues
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($totalDiscounts, 2) }}
                                                Mga</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-start border-success border-4 shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Nombre
                                                d'Achats</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPurchases }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-basket fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Panier
                                                Moyen</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($averagePurchaseValue, 2) }} Mga</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- On charge le script spécifique à cette page --}}
    @vite('resources/js/purchases-index.js')
@endpush
