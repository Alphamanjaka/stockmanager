@extends('layouts.app-back-office')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800">Gestion des Stocks</h1>
            <a href="{{ route('admin.movements.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau Mouvement
            </a>
        </div>

        {{-- Dashboard Section : Chart + Stats side by side --}}
        <div class="row mb-4">
            {{-- Chart (Left, larger) --}}
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line me-1"></i> Valorisation (30 jours)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="stockValueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats (Right, stacked) --}}
            <div class="col-lg-4">
                {{-- Dormant --}}
                <div class="card border-warning shadow mb-3">
                    <div class="card-header bg-warning text-dark py-2">
                        <h6 class="mb-0 small fw-bold"><i class="fas fa-bed me-1"></i> Produits Dormants (> 60j)</h6>
                    </div>
                    <div class="card-body p-2" style="max-height: 140px; overflow-y: auto;">
                        @if ($dormantProducts->isEmpty())
                            <p class="text-muted mb-0 small">R.A.S.</p>
                        @else
                            <ul class="list-group list-group-flush small">
                                @foreach ($dormantProducts as $product)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                        <span class="text-truncate" style="max-width: 150px;">{{ $product->name }}</span>
                                        <span class="badge bg-secondary rounded-pill">{{ $product->quantity_stock }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Rotation --}}
                <div class="card border-success shadow">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0 small fw-bold"><i class="fas fa-sync-alt me-1"></i> Top Rotation</h6>
                    </div>
                    <div class="card-body p-2" style="max-height: 140px; overflow-y: auto;">
                        @if ($rotationStats->isEmpty())
                            <p class="text-muted mb-0 small">Pas assez de données.</p>
                        @else
                            <ul class="list-group list-group-flush small">
                                @foreach ($rotationStats as $stat)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                        <span class="text-truncate"
                                            style="max-width: 150px;">{{ $stat->product->name }}</span>
                                        <span class="badge bg-primary rounded-pill">-{{ $stat->total_out }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters & List Section --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Historique des Mouvements</h6>
            </div>

            <div class="card-body border-bottom bg-light">
                <form method="GET" action="{{ route('admin.movements.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Recherche</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Produit, motif..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Entrées</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Sorties</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Période</label>
                        <div class="input-group input-group-sm">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            <span class="input-group-text">-</span>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrer</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.movements.index') }}" class="btn btn-outline-secondary btn-sm w-100"
                            title="Reset"><i class="fas fa-undo"></i></a>
                    </div>
                </form>
            </div>

            {{-- Table View (Compact) --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Produit</th>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Stock (Av &rarr; Ap)</th>
                                <th>Raison</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockMovements as $movement)
                                @php
                                    $isManual =
                                        str_contains(strtolower($movement->reason), 'ajustement') ||
                                        str_contains(strtolower($movement->reason), 'manuel');
                                    $isSale =
                                        str_contains(strtolower($movement->reason), 'vente') ||
                                        str_contains(strtolower($movement->reason), 'sale');
                                    $isPurchase =
                                        str_contains(strtolower($movement->reason), 'achat') ||
                                        str_contains(strtolower($movement->reason), 'pur');

                                    $badgeClass = 'bg-secondary';
                                    $badgeText = 'Autre';

                                    if ($isManual) {
                                        $badgeClass = 'bg-info text-dark';
                                        $badgeText = 'Ajustement';
                                    } elseif ($movement->type === 'in') {
                                        $badgeClass = 'bg-success';
                                        $badgeText = $isPurchase ? 'Achat' : 'Entrée';
                                    } elseif ($movement->type === 'out') {
                                        $badgeClass = 'bg-danger';
                                        $badgeText = $isSale ? 'Vente' : 'Sortie';
                                    }

                                    $isLowStock = $movement->stock_after <= ($movement->product->alert_stock ?? 0);
                                @endphp
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <div class="fw-bold">{{ $movement->created_at->format('d/m/Y') }}</div>
                                        <div class="small text-muted">{{ $movement->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $movement->product->name }}</span>
                                        @if ($isLowStock)
                                            <i class="fas fa-exclamation-triangle text-danger ms-1" title="Stock bas"></i>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                                    <td class="fw-bold {{ $movement->type === 'in' ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->type === 'in' ? '+' : '-' }}{{ abs($movement->quantity) }}
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $movement->stock_before }}</span>
                                        <i class="fas fa-long-arrow-alt-right mx-1 text-muted small"></i>
                                        <span class="fw-bold">{{ $movement->stock_after }}</span>
                                    </td>
                                    <td class="text-muted small text-truncate" style="max-width: 200px;"
                                        title="{{ $movement->reason }}">
                                        {{ $movement->reason }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucun mouvement trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="card-footer py-3">
                <div class="d-flex justify-content-center">
                    {{ $stockMovements->links() }}
                </div>
            </div>
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
