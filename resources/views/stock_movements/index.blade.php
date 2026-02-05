@extends('layouts.app-back-office')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800">Fil d'actualité des Stocks</h1>
            <a href="{{ route('admin.movements.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau Mouvement
            </a>
        </div>

        <!-- Intelligence Métier : Cartes d'analyse -->
        <div class="row mb-4">
            <!-- Produits Dormants -->
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card border-warning shadow h-100">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-bed me-1"></i> Produits Dormants (> 60 jours sans sortie)
                    </div>
                    <div class="card-body">
                        @if ($dormantProducts->isEmpty())
                            <p class="text-muted mb-0 small">Aucun produit dormant détecté.</p>
                        @else
                            <ul class="list-group list-group-flush small">
                                @foreach ($dormantProducts as $product)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        {{ $product->name }}
                                        <span class="badge bg-secondary rounded-pill">{{ $product->quantity_stock }} en
                                            stock</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Rotation -->
            <div class="col-md-6">
                <div class="card border-success shadow h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-sync-alt me-1"></i> Top Rotation (Sorties rapides)
                    </div>
                    <div class="card-body">
                        @if ($rotationStats->isEmpty())
                            <p class="text-muted mb-0 small">Pas assez de données.</p>
                        @else
                            <ul class="list-group list-group-flush small">
                                @foreach ($rotationStats as $stat)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        {{ $stat->product->name }}
                                        <span class="badge bg-primary rounded-pill">{{ $stat->total_out }} sortis</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget : Évolution de la Valeur du Stock -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-1"></i> Évolution de la Valeur du Stock (30 jours)
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px;">
                    <canvas id="stockValueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtres & Recherche</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.movements.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Produit, motif..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Tous</option>
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Entrées (Vert)</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Sorties (Rouge)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Du</label>
                        <input type="date" name="date_from" id="date_from" class="form-control"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Au</label>
                        <input type="date" name="date_to" id="date_to" class="form-control"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Timeline des Mouvements -->
        <div class="timeline-container">
            @forelse($stockMovements as $movement)
                @php
                    // Logique d'affichage (Couleurs et Badges)
$isManual =
    str_contains(strtolower($movement->reason), 'ajustement') ||
    str_contains(strtolower($movement->reason), 'manuel');
$isSale =
    str_contains(strtolower($movement->reason), 'vente') ||
    str_contains(strtolower($movement->reason), 'sale');
$isPurchase =
    str_contains(strtolower($movement->reason), 'achat') ||
    str_contains(strtolower($movement->reason), 'pur');

$color = 'primary'; // Bleu par défaut
$icon = 'info-circle';
$badgeText = 'Autre';

if ($isManual) {
    $color = 'info'; // Bleu cyan pour manuel
    $icon = 'edit';
    $badgeText = 'Ajustement';
} elseif ($movement->type === 'in') {
    $color = 'success'; // Vert pour entrée
    $icon = 'arrow-down';
    $badgeText = $isPurchase ? 'Achat' : 'Entrée';
} elseif ($movement->type === 'out') {
    $color = 'danger'; // Rouge pour sortie
    $icon = 'arrow-up';
    $badgeText = $isSale ? 'Vente' : 'Sortie';
                    }

                    // Alerte de stock (Intelligence Métier)
                    $isLowStock = $movement->stock_after <= ($movement->product->alert_stock ?? 0);

                    // Valorisation (Prix du produit * Quantité mouvementée)
                    $valuation = $movement->product->price * abs($movement->quantity);
                @endphp

                <div class="card mb-3 border-start border-5 border-{{ $color }} shadow-sm"
                    style="border-left: 5px solid var(--bs-{{ $color }});">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <!-- Icône et Date -->
                            <div class="col-auto text-center pe-3 border-end">
                                <div class="text-{{ $color }} mb-1">
                                    <i class="fas fa-{{ $icon }} fa-2x"></i>
                                </div>
                                <div class="small text-muted fw-bold">{{ $movement->created_at->format('d/m/Y') }}</div>
                                <div class="small text-muted">{{ $movement->created_at->format('H:i') }}</div>
                            </div>

                            <!-- Détails du mouvement -->
                            <div class="col ps-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1 fw-bold text-dark">
                                            {{ $movement->product->name }}
                                            <span class="badge bg-{{ $color }} ms-2">{{ $badgeText }}</span>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            {{ $movement->reason }}
                                        </p>

                                        <!-- Alerte Rupture -->
                                        @if ($isLowStock)
                                            <div class="text-danger small mt-1">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Alerte Rupture :</strong> Stock après mouvement
                                                ({{ $movement->stock_after }})
                                                sous le seuil
                                                ({{ $movement->product->alert_stock }}).
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Quantités et Valeurs -->
                                    <div class="text-end">
                                        <div class="h4 fw-bold text-{{ $color }} mb-0">
                                            {{ $movement->type === 'in' ? '+' : '-' }}{{ abs($movement->quantity) }}
                                        </div>
                                        <div class="small text-muted">
                                            Stock : {{ $movement->stock_before }} <i
                                                class="fas fa-long-arrow-alt-right"></i>
                                            <strong>{{ $movement->stock_after }}</strong>
                                        </div>
                                        <div class="small text-muted mt-1" title="Valeur estimée au prix actuel">
                                            <i class="fas fa-coins"></i> {{ number_format($valuation, 2) }} €
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-light text-center py-5 border">
                    <i class="fas fa-box-open fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted mb-0">Aucun mouvement de stock trouvé pour ces critères.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $stockMovements->links() }}
        </div>
    </div>
@endsection


@push('scripts')
    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('stockValueChart').getContext('2d');
            const data = @json($stockValueEvolution);

            const labels = data.map(item => item.date);
            const values = data.map(item => item.value);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Valeur Totale (€)',
                        data: values,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        pointRadius: 3,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#4e73df',
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: '#4e73df',
                        pointHoverBorderColor: '#4e73df',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.3 // Courbe lissée
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toLocaleString('fr-FR', {
                                        style: 'currency',
                                        currency: 'EUR'
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                borderDash: [2],
                                drawBorder: false
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
