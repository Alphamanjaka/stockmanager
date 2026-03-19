@extends('layouts.app-back-office')

@section('title', 'Détail de la Catégorie : ' . $category->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <div>
                <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Modifier
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Informations Générales</h5>
                    </div>
                    <div class="card-body">
                        <h2 class="mb-3">{{ $category->name }}</h2>
                        <p class="text-muted">{{ $category->description ?? 'Aucune description pour cette catégorie.' }}</p>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Catégorie Parente :</strong>
                                @if ($category->parent)
                                    <a href="{{ route('admin.categories.show', $category->parent->id) }}"
                                        class="badge bg-info text-decoration-none">{{ $category->parent->name }}</a>
                                @else
                                    <span class="badge bg-secondary">Racine (Aucun parent)</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Date de création :</strong> {{ $category->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 bg-light">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="text-muted">Produits Associés</h6>
                        <h1 class="display-4 fw-bold text-primary">{{ $category->products_count }}</h1>
                        <p class="mb-0">Valeur estimée du stock :</p>
                        <h4 class="text-success">{{ number_format($stockValue, 2) }} MGA</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Produits dans cette catégorie</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td><a href="{{ route('admin.products.show', $product->id) }}"
                                        class="text-decoration-none">{{ $product->name }}</a></td>
                                <td>{{ number_format($product->price, 2) }} MGA</td>
                                <td><span
                                        class="badge {{ $product->quantity_stock <= $product->alert_stock ? 'bg-danger' : 'bg-success' }}">{{ $product->quantity_stock }}</span>
                                </td>
                                <td><a href="{{ route('admin.products.show', $product->id) }}"
                                        class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Aucun produit dans cette catégorie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">{{ $products->links() }}</div>
            </div>
        </div>
    </div>
@endsection
