@extends('layouts.app-back-office')

@section('title', 'Achats')
@section('actions')
    <a href="{{ route('admin.purchases.createFromShortage') }}" class="btn btn-warning">
        <i class="fas fa-exclamation-triangle"></i> Commande par rupture
    </a>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouvel Achat
    </a>
@endsection
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

    {{-- KPI Section Compact --}}
    <div class="card shadow-sm mb-3 border-0 bg-light">
        <div class="card-body py-2">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-3 border-end-md">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-credit-card text-primary me-2"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Total Achats (Net)</span>
                            <span class="h6 mb-0 font-weight-bold">{{ number_format($totalSpent, 2) }} <small>MGA</small></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 border-end-md">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-percent text-warning me-2"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Remises Obtenues</span>
                            <span class="h6 mb-0 font-weight-bold">{{ number_format($totalDiscounts, 2) }} <small>MGA</small></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 border-end-md">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-shopping-basket text-success me-2"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Nombre d'Achats</span>
                            <span class="h6 mb-0 font-weight-bold">{{ $totalPurchases }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-chart-pie text-info me-2"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Panier Moyen</span>
                            <span class="h6 mb-0 font-weight-bold">{{ number_format($averagePurchaseValue, 2) }} <small>MGA</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
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
                    <p class="text-center text-muted">Statistiques détaillées à venir (graphiques d'évolution, top fournisseurs, etc.).</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- On charge le script spécifique à cette page --}}
    @vite('resources/js/purchases-index.js')
@endpush
