@extends('layouts.app-back-office')

@section('title', 'Back Office - Gestion des Produits')
@section('content')
    <!-- Statistiques Résumées -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center h-100 bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Total Produits</h6>
                    <p class="card-text fs-4 fw-bold">{{ $totalProducts }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center h-100 bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Stock Bas</h6>
                    <p class="card-text fs-4 fw-bold">{{ $lowStockProducts }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center h-100 bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Catégories</h6>
                    <p class="card-text fs-4 fw-bold">{{ $totalCategories }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center h-100 bg-info text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Fournisseurs</h6>
                    <p class="card-text fs-4 fw-bold">{{ $totalSuppliers }}</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin des Statistiques Résumées -->
    <!-- Modules de gestion -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modules de Gestion</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-lg btn-primary w-100 py-3">
                                <i class="fas fa-box mr-2"></i> Gestion des Produits
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-lg btn-success w-100 py-3">
                                <i class="fas fa-list mr-2"></i> Gestion des Catégories
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.movements.index') }}" class="btn btn-lg btn-info w-100 py-3">
                                <i class="fas fa-arrows-alt mr-2"></i> Mouvements de Stock
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-lg btn-warning w-100 py-3">
                                <i class="fas fa-truck mr-2"></i> Gestion des Fournisseurs
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-lg btn-danger w-100 py-3">
                                <i class="fas fa-shopping-basket mr-2"></i> Achats
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="#" class="btn btn-lg btn-secondary w-100 py-3">
                                <i class="fas fa-cash-register mr-2"></i> Ventes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin des modules de gestion -->

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Évolution des Ventes</h5>
                    <form action="{{ route('admin.dashboard') }}" method="GET" id="periodForm">
                        <select name="period" class="form-select form-select-sm"
                            onchange="document.getElementById('periodForm').submit()">
                            <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>7 Derniers Jours</option>
                            <option value="1month" {{ $period == '1month' ? 'selected' : '' }}>4 Dernières Semaines
                            </option>
                            <option value="1year" {{ $period == '1year' ? 'selected' : '' }}>12 Derniers Mois</option>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="salesChart"></canvas>
                    </div>

                    <div class="row mt-4 text-center">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Aujourd'hui</small>
                            <span class="fw-bold">{{ number_format($salesToday, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ce Mois</small>
                            <span class="fw-bold">{{ number_format($salesThisMonth, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Total Ventes</small>
                            <span class="fw-bold">{{ $totalSales }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique des ventes du back office -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Statistiques des Ventes</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm text-center">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Ventes Aujourd'hui</h6>
                                    <p class="card-text fs-4 fw-bold text-success">
                                        {{ number_format($salesToday, 2, ',', ' ') }} €</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm text-center">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Ventes ce Mois-ci</h6>
                                    <p class="card-text fs-4 fw-bold text-primary">
                                        {{ number_format($salesThisMonth, 2, ',', ' ') }} €</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm text-center">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Nombre de Ventes</h6>
                                    <p class="card-text fs-4 fw-bold text-info">{{ $totalSales }}</p>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');

    // Récupération des données envoyées par le contrôleur
    const labels = @json($labels);
    const values = @json($values);

    new Chart(ctx, {
        type: 'bar', // Type de graphique (barre pour les ventes mensuelles)
        data: {
            labels: labels,
            datasets: [{
                label: 'Chiffre d\'affaires (€)',
                data: values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3, // Arrondi des lignes
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return value + ' €'; }
                    }
                }
            }
        }
    });
</script>
@endpush
