@extends('layouts.app-back-office')

@section('title', 'Gestion des Catégories')
@section('content')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">Ajouter une catégorie</a>
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
                            <a class="text-decoration-none text-white" href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}">Name
                                @if (request('sort') == 'name')
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a class="text-decoration-none text-white" href="{{ request()->fullUrlWithQuery(['sort' => 'parent_id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}">Catégorie Parente
                                @if (request('sort') == 'parent_id')
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
                                @endif
                            </a>

                        </th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <th scope="row">{{ $category->id }}</th>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->parent ? $category->parent->name : 'Aucun parent' }}</td>
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
