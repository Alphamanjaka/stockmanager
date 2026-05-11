@extends('layouts.app-back-office')

@section('title', 'Détails du Produit : ' . $product->name)

@section('content')
    <div class="container-fluid py-4">
        {{-- En-tête avec informations de base --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1" id="display-name">{{ $product->name }}</h1>
                    <p class="text-muted mb-0">
                        Catégorie : <span id="display-category"
                            class="badge bg-info text-dark">{{ $product->category->name ?? 'N/A' }}</span> |
                        Nombre de variantes : <span id="variant-count" class="fw-bold">{{ $variants->count() }}</span>
                    </p>
                </div>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal">
                    <i class="fas fa-edit me-1"></i> Modifier infos générales
                </button>
            </div>
        </div>

        <div class="row">
            {{-- Graphique d'évolution du stock --}}
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Évolution du Stock Global</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="stockEvolutionChart" height="100"></canvas>
                    </div>
                </div>

                {{-- Table des Variantes --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Variantes de couleurs & Stocks</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Couleur</th>
                                    <th>Prix (MGA)</th>
                                    <th>Stock actuel</th>
                                    <th>Seuil d'alerte</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="variants-table-body">
                                @forelse ($variants as $variant)
                                    <tr>
                                        <td><span class="badge" style="background-color: {{ $variant->color->code }}">
                                            </span>
                                            {{ $variant->color->name }}</td>
                                        <td>{{ number_format($variant->price, 2) }} MGA</td>
                                        <td><span
                                                class="badge {{ $variant->stock <= $variant->alert_stock ? 'bg-danger' : 'bg-success' }}">{{ $variant->stock }}</span>
                                        </td>
                                        <td>{{ $variant->alert_stock }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.products.edit', $variant->id) }}"
                                                class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Aucune variante pour ce produit.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Historique des mouvements --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Historique Mouvements</h5>
                        <select id="variant-filter" class="form-select form-select-sm w-50">
                            <option value="">Toutes les variantes</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->color->name }} (ID: {{ $variant->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="history-list">
                            {{-- Chargé via AJAX --}}
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <button id="load-more-history" class="btn btn-sm btn-link">Voir plus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edition Infos Générales --}}
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editProductForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" name="name" class="form-control" value="{{ $product->name }}"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="form-select">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $product->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const PRODUCT_ID = {{ $product->id }};
        let stockChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadVariants();
            loadHistory();
            loadChart();

            // Gestion de l'édition générale AJAX
            document.getElementById('editProductForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());

                fetch(`/admin/products/main/${PRODUCT_ID}/update-details`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            document.getElementById('display-name').innerText = res.data.name;
                            document.getElementById('display-category').innerText = res.data
                                .category_name;
                            bootstrap.Modal.getInstance(document.getElementById('editProductModal'))
                                .hide();
                        }
                    });
            });

            // Filtre d'historique
            document.getElementById('variant-filter').addEventListener('change', function() {
                loadHistory(this.value);
            });
        });

        function loadHistory(variantId = '') {
            const list = document.getElementById('history-list');
            list.innerHTML = '<li class="list-group-item text-center">Chargement...</li>';

            fetch(`/api/products/${PRODUCT_ID}/movements?variant_id=${variantId}`)
                .then(res => res.json())
                .then(res => {
                    list.innerHTML = res.data.map(m => `
                <li class="list-group-item border-0 border-bottom">
                    <div class="d-flex justify-content-between">
                        <small class="fw-bold text-uppercase">${m.type === 'in' ? 'Entrée' : 'Sortie'}</small>
                        <small class="text-muted">${new Date(m.created_at).toLocaleDateString()}</small>
                    </div>
                    <div class="small">${m.product_color.color.name} : <strong>${m.quantity} unités</strong></div>
                    <div class="text-muted extra-small">${m.reason || ''}</div>
                </li>
            `).join('') || '<li class="list-group-item text-center text-muted">Aucun mouvement</li>';
                });
        }

        // Initialize chart with data passed from the controller
        const stockEvolutionData = @json($stockEvolution);
        const ctx = document.getElementById('stockEvolutionChart').getContext('2d');
        stockChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: stockEvolutionData.map(item => item.x),
                datasets: [{
                    label: 'Stock Global',
                    data: stockEvolutionData.map(item => item.y),
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    }, // Use 'time' scale for dates
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <style>
        .extra-small {
            font-size: 0.75rem;
        }
    </style>
@endpush
```
