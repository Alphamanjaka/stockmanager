@extends('layouts.app-back-office')

@section('title', 'Gestion des Catégories')
@section('content')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">Ajouter une catégorie</a>

    {{-- Dashboard Rapide --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-folder"></i> Total Catégories</h6>
                    <h3 class="mb-0">{{ $stats['total_categories'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-box-seam"></i> Produits Classés</h6>
                    <h3 class="mb-0">{{ $stats['total_products_linked'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-trophy"></i> Plus Populaire</h6>
                    <p class="mb-0 fs-5">{{ $stats['most_populated']->name ?? 'N/A' }}
                        <small>({{ $stats['most_populated']->products_count ?? 0 }} produits)</small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre de recherche -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ url('/admin/categories') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher une catégorie..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">Toutes les catégories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                    <a href="{{ url('/admin/categories') }}" class="btn btn-outline-secondary" rel="noopener"
                        target="_blank">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    <!-- Fin du filtre de recherche -->
    <!-- Tableau des catégories -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">
                            <a class="text-decoration-none text-white"
                                href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}">Name
                                @if (request('sort') == 'name')
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a class="text-decoration-none text-white"
                                href="{{ request()->fullUrlWithQuery(['sort' => 'parent_id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}">Catégorie
                                Parente
                                @if (request('sort') == 'parent_id')
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
                                @endif
                            </a>

                        </th>
                        <th scope="col">Produits</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <th scope="row">{{ $category->id }}</th>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->parent ? $category->parent->name : '--' }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                <a href="{{ route('admin.categories.edit', $category->id) }}"
                                    class="btn btn-sm btn-primary">Modifier</a>
                                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- Fin du tableau des catégories -->

    <!-- Paginateur -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $categories->links() }}
    </div>
@endsection
