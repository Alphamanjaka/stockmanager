@extends('layouts.app-back-office')

@section('title', 'Gestion des Catégories')
@section('content')
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800">Catégories</h1>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Catégorie
            </a>
        </div>

        {{-- KPI Section --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card border-start border-primary border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Catégories
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_categories'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-folder fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-start border-success border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Produits Classés
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_products_linked'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box-open fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-start border-info border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Plus Populaire</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 text-truncate">
                                    {{ $stats['most_populated']->name ?? 'N/A' }}</div>
                                <div class="small text-muted">{{ $stats['most_populated']->products_count ?? 0 }} produits
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-trophy fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <ul class="nav nav-tabs mb-4" id="categoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button"
                    role="tab" aria-controls="list" aria-selected="true">
                    <i class="fas fa-list me-2"></i>Liste des Catégories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart" type="button"
                    role="tab" aria-controls="chart" aria-selected="false">
                    <i class="fas fa-chart-pie me-2"></i>Répartition (Graphique)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="categoryTabsContent">
            {{-- Tab 1: Liste et Filtres --}}
            <div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="list-tab">
                {{-- Filters --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filtres & Recherche</h6>
                    </div>
                    <div class="card-body bg-light">
                        <form action="{{ url('/admin/categories') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small text-muted">Recherche</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Nom de la catégorie..." value="{{ request('search') }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted">Filtrer par</label>
                                <select name="category" class="form-select">
                                    <option value="">Toutes les catégories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ request('category') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                                    <a href="{{ url('/admin/categories') }}"
                                        class="btn btn-outline-secondary w-100">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Table --}}
                <div class="card shadow">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark text-decoration-none">
                                                Nom <i
                                                    class="fas fa-sort{{ request('sort') == 'name' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'parent_id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark text-decoration-none">
                                                Parent <i
                                                    class="fas fa-sort{{ request('sort') == 'parent_id' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                            </a>
                                        </th>
                                        <th>Produits</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td class="ps-4 text-muted small">#{{ $category->id }}</td>
                                            <td class="fw-bold text-dark">{{ $category->name }}</td>
                                            <td>
                                                @if ($category->parent)
                                                    <span
                                                        class="badge bg-light text-dark border">{{ $category->parent->name }}</span>
                                                @else
                                                    <span class="text-muted small">Racine</span>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-secondary">{{ $category->products_count }}</span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.categories.edit', $category->id) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.categories.destroy', $category->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer py-3">
                        <div class="d-flex justify-content-center">
                            {{ $categories->links() }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Graphique --}}
            <div class="tab-pane fade" id="chart" role="tabpanel" aria-labelledby="chart-tab">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Répartition des produits par catégorie mère</h6>
                    </div>
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-8 col-lg-6">
                                <div style="height: 400px;">
                                    <canvas id="categoryPieChart"></canvas>
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
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('categoryPieChart');
            if (ctx) {
                const labels = @json($distribution['labels']);
                const data = @json($distribution['data']);

                // Palette de couleurs douces
                const backgroundColors = [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#f8f9fc', '#2e59d9', '#17a673'
                ];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: backgroundColors,
                            hoverBackgroundColor: backgroundColors,
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        },
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        cutoutPercentage: 80,
                    },
                });
            }
        });
    </script>
@endpush
