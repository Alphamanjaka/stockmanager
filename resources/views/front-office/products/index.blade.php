@extends('layouts.app-front-office')

@section('title', 'Catalogue Produits')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <form action="{{ route('saler.products.index') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0"
                               placeholder="Rechercher par nom ou référence..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Désignation</th>
                            <th class="text-end">Prix</th>
                            <th class="text-center">Stock</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <small class="text-muted">{{ $product->category->name ?? 'Non catégorisé' }}</small>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    {{ number_format($product->price, 0, ',', ' ') }} Mga
                                </td>
                                <td class="text-center">
                                    @if($product->quantity_stock <= $product->alert_stock)
                                        <span class="badge bg-danger rounded-pill px-3">
                                            {{ $product->quantity_stock }}
                                        </span>
                                    @else
                                        <span class="badge bg-success rounded-pill px-3">
                                            {{ $product->quantity_stock }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    {{-- Lien vers l'ajout au panier si vous implémentez cette fonctionnalité plus tard --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Aucun produit trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $products->links() }}
        </div>
    </div>
@endsection
