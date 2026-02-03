@extends('layouts.app-back-office')

@section('title', 'Liste des Produits')

@section('content')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary mb-3">+ Ajouter</a>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-star-fill"></i> Produit Favori (le plus vendu)</h5>
                    @if ($mostSoldProduct && $mostSoldProduct->product)
                        <p class="card-text fs-4 fw-bold">{{ $mostSoldProduct->product->name }}</p>
                        <p class="mb-0">Vendu {{ $mostSoldProduct->total_sold }} fois.</p>
                    @else
                        <p class="card-text">Aucune vente enregistrée pour le moment.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-graph-down"></i> Produit le Moins Vendu</h5>
                    @if ($leastSoldProduct && $leastSoldProduct->product)
                        <p class="card-text fs-4 fw-bold">{{ $leastSoldProduct->product->name }}</p>
                        <p class="mb-0">Vendu {{ $leastSoldProduct->total_sold }} fois.</p>
                    @else
                        <p class="card-text">Aucune vente enregistrée pour le moment.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ url('admin/products') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..."
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
                    <a href="{{ url('admin/products') }}" class="btn btn-outline-secondary"
                        rel="noopener">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                class="text-white text-decoration-none">
                                ID @if (request('sort') == 'id')
                                    <i
                                        class="bi bi-sort-{{ request('order') == 'asc' ? 'numeric-down' : 'numeric-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                class="text-white text-decoration-none">
                                Nom @if (request('sort') == 'name')
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>Catégorie</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'quantity_stock', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                class="text-white text-decoration-none">
                                Stock @if (request('sort') == 'quantity_stock')
                                    <i
                                        class="bi bi-sort-{{ request('order') == 'asc' ? 'numeric-down' : 'numeric-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'price', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                class="text-white text-decoration-none">
                                Prix @if (request('sort') == 'price')
                                    <i
                                        class="bi bi-sort-{{ request('order') == 'asc' ? 'numeric-down' : 'numeric-up' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                <a href="{{ route('admin.products.show', $product->id) }}"
                                    class="text-decoration-none">{{ $product->name }}</a>
                            </td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                            <td>
                                <span
                                    class="badge {{ $product->quantity_stock <= $product->alert_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ $product->quantity_stock }}
                                </span>
                            </td>
                            <td>{{ number_format($product->price, 2) }} €</td>
                            <td>
                                <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-sm btn-info"
                                    title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-primary"
                                    title="Modifier"><i class="bi bi-pencil-square"></i></a>
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i
                                            class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Aucun produit trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
