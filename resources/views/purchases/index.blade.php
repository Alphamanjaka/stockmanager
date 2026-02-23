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

    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvel Achat
            </a>
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
                <div class="py-4">
                    {{-- Filters --}}
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtres & Recherche</h6>
                        </div>
                        <div class="card-body bg-light">
                            <form action="{{ url('/admin/purchases') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small text-muted">Recherche</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Référence, Fournisseur..." value="{{ request('search') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Statut</label>
                                    <select name="state" class="form-select">
                                        <option value="">Tous les statuts</option>
                                        <option value="Draft" @selected(request('state') == 'Draft')>Brouillon</option>
                                        <option value="Ordered" @selected(request('state') == 'Ordered')>Commandé</option>
                                        <option value="Received" @selected(request('state') == 'Received')>Reçu</option>
                                        <option value="Paid" @selected(request('state') == 'Paid')>Payé</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                                        <a href="{{ url('/admin/purchases') }}"
                                            class="btn btn-outline-secondary w-100">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="card shadow">
                        <div class="card-header py-3 bg-white border-bottom-0">
                            <h6 class="m-0 font-weight-bold text-secondary">Transactions Récentes</h6>
                        </div>
                        <div class="card-body p-2">
                            {{-- Tabulator Container --}}
                            <div id="purchases-table"></div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation de Tabulator
            var table = new Tabulator("#purchases-table", {
                ajaxURL: "{{ route('admin.purchases.get-purchases-api') }}", // URL de l'API
                ajaxConfig: "GET", // Méthode HTTP
                pagination: "remote", // Active la pagination côté serveur
                paginationSize: 10, // Nombre d'éléments par page
                paginationMode: "remote", // Indispensable pour que Tabulator accepte l'objet JSON de pagination
                paginationSizeSelector: [10, 25, 50, 100], // Sélecteur de taille
                filterMode: "remote", // Filtrage côté serveur
                sortMode: "remote", // Tri côté serveur
                // Avec la PurchaseApiResourceCollection, le backend retourne EXACTEMENT
                // le format { "last_page": ..., "data": [...] } attendu par Tabulator.
                // Il n'y a donc plus besoin de 'ajaxResponse' ou 'dataReceiveParams'.
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "Aucun achat trouvé",
                columns: [{
                        title: "Reference",
                        field: "reference",
                        headerFilter: "input"
                    },
                    {
                        title: "Date",
                        field: "date",
                        hozAlign: "center"
                    },
                    {
                        title: "State",
                        field: "state",
                        formatter: function(cell) {
                            var value = cell.getValue();
                            var badgeClass = 'bg-secondary';
                            var label = value;

                            if (value === 'Draft') {
                                badgeClass = 'bg-secondary';
                            } else if (value === 'Ordered') {
                                badgeClass = 'bg-info text-dark';
                            } else if (value === 'Received') {
                                badgeClass = 'bg-success';
                            } else if (value === 'Paid') {
                                badgeClass = 'bg-primary';
                            }

                            return `<span class="badge ${badgeClass}">${value}</span>`;
                        },
                        hozAlign: "center"
                    },
                    {
                        title: "Montant Total",
                        field: "total_amount",
                        hozAlign: "right"
                    },
                    {
                        title: "Remise",
                        field: "discount",
                        formatter: function(cell) {
                            var val = cell.getValue();
                            return val > 0 ?
                                `<span class="text-danger">-${new Intl.NumberFormat('fr-FR').format(val)} Mga</span>` :
                                '-';
                        },
                        hozAlign: "right"
                    },
                    {
                        title: "Total Net",
                        field: "total_net",
                        hozAlign: "right",
                        formatter: function(cell) {
                            return `<span class="fw-bold text-success">${cell.getValue()}</span>`;
                        }
                    },
                    {
                        title: "Actions",
                        field: "urls",
                        formatter: function(cell) {
                            var urls = cell.getValue();
                            return `
                            <div class="btn-group">
                                <a href="${urls.show}" class="btn btn-sm btn-outline-secondary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="${urls.edit}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="${urls.destroy}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet achat ?');">
                                    <input type="hidden" name="_token" value="${urls.csrf}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                        },
                        hozAlign: "center",
                        headerSort: false
                    }
                ],
            });
        });
    </script>
@endpush
