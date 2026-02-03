@extends('layouts.app-back-office')
@section('title', 'Détail du Produit : ' . $product->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <div>
                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Modifier
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Product Details Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-box"></i> Informations sur le Produit</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Nom:</strong>
                                <span>{{ $product->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Catégorie:</strong>
                                <span>{{ $product->category->name ?? 'Non définie' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Prix de vente:</strong>
                                <span class="fw-bold text-success">{{ number_format($product->price, 2) }} €</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Description:</strong>
                                <p class="mt-2 text-muted">{{ $product->description ?? 'Aucune description' }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Stock Details Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-warehouse"></i> État du Stock</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Stock Actuel:</strong>
                                <span
                                    class="badge fs-6 {{ $product->quantity_stock <= $product->alert_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ $product->quantity_stock }} unités
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Seuil d'alerte:</strong>
                                <span>{{ $product->alert_stock }} unités</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Créé le:</strong>
                                <span>{{ $product->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Mis à jour le:</strong>
                                <span>{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    {{-- Stock Evolution Chart --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Évolution du Stock</h5>
        </div>
        <div class="card-body" width="100%" height="300px">
            <canvas id="stockChart"></canvas>
        </div>
    </div>

        {{-- Stock Movements History --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-arrows-alt"></i> Historique des Mouvements</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Raison</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{!! $movement->type === 'in'
                                    ? '<span class="badge bg-success">Entrée</span>'
                                    : '<span class="badge bg-danger">Sortie</span>' !!}</td>
                                <td class="fw-bold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity }}</td>
                                <td>{{ $movement->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">Aucun mouvement de stock pour ce produit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3 d-flex justify-content-center">{{ $stockMovements->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Nous avons besoin de Chart.js et de son adaptateur de date pour dessiner le graphique --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log('Initialisation du graphique de l\'évolution du stock...');
        const ctx = document.getElementById('stockChart').getContext('2d');

        // Les données sont passées depuis le contrôleur en JSON
        const chartLabels = {!! $chartLabels !!};
        const chartData = {!! $chartData !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Quantité en Stock',
                    data: chartData,
                    borderColor: 'rgba(23, 162, 184, 1)', // Un joli bleu/vert
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    borderWidth: 2,
                    stepped: true, // Parfait pour montrer des niveaux de stock constants entre les changements
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time', // Indique à Chart.js d'utiliser une échelle de temps
                        time: {
                            unit: 'day',
                            tooltipFormat: 'dd/MM/yyyy HH:mm' // Format pour l'infobulle
                        },
                        title: { display: true, text: 'Date' }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quantité' }
                    }
                }
            }
        });
    });
</script>
@endpush
