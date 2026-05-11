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
                        Nombre de variantes : <span id="variant-count" class="fw-bold">...</span>
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
                                {{-- Chargé via AJAX --}}
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
                            <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
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

        function loadVariants() {
            fetch(`/api/products/${PRODUCT_ID}/variants`)
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('variants-table-body');
                    const filter = document.getElementById('variant-filter');
                    document.getElementById('variant-count').innerText = res.count;

                    tbody.innerHTML = res.data.map(v => `
                <tr>
                    <td><span class="badge" style="background-color: ${v.color.code}"> </span> ${v.color.name}</td>
                    <td>${v.price}</td>
                    <td><span class="badge ${v.stock <= v.alert_stock ? 'bg-danger' : 'bg-success'}">${v.stock}</span></td>
                    <td>${v.alert_stock}</td>
                    <td class="text-end">
                        <a href="/admin/products/${v.id}/edit" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
            `).join('');

                    // Remplir le filtre d'historique
                    res.data.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.id;
                        opt.textContent = v.color.name;
                        filter.appendChild(opt);
                    });
                });
        }

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

        function loadChart() {
            fetch(`/api/products/${PRODUCT_ID}/stock-evolution`)
                .then(res => res.json())
                .then(res => {
                    const ctx = document.getElementById('stockEvolutionChart').getContext('2d');
                    if (stockChart) stockChart.destroy();

                    stockChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: res.labels,
                            datasets: [{
                                label: 'Stock Global',
                                data: res.data,
                                borderColor: '#4e73df',
                                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        }
    </script>
    <style>
        .extra-small {
            font-size: 0.75rem;
        }
    </style>
@endpush
```
