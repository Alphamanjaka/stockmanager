@extends('layouts.app-back-office')

@section('title', 'Liste des Produits')

@section('content')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary mb-3">+ Ajouter</a>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ url('/products') }}" method="GET" class="row g-3">
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
                    <a href="{{ url('admin/products') }}" class="btn btn-outline-secondary" rel="noopener"
                        target="_blank">Réinitialiser</a>
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
                                    <i class="bi bi-sort-{{ request('order') == 'asc' ? 'alpha-down' : 'alpha-up' }}"></i>
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
                    </tr>
                </thead>

                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>
                                <span
                                    class="badge {{ $product->quantity_stock <= $product->alert_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ $product->quantity_stock }}
                                </span>
                            </td>
                            <td>{{ number_format($product->price, 2) }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
