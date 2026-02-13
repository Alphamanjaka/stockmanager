@extends('layouts.app-back-office')

@section('title', 'Gestion des Produits')

@section('content')
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800">Produits</h1>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau Produit
            </a>
        </div>

        {{-- KPI Section --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Top Vente</div>
                                @if ($mostSoldProduct && $mostSoldProduct->product)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $mostSoldProduct->product->name }}</div>
                                    <div class="small text-muted">Vendu {{ $mostSoldProduct->total_sold }} fois</div>
                                @else
                                    <div class="text-muted small">Aucune vente enregistrée</div>
                                @endif
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-trophy fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-start border-warning border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Moins Vendu</div>
                                @if ($leastSoldProduct && $leastSoldProduct->product)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $leastSoldProduct->product->name }}</div>
                                    <div class="small text-muted">Vendu {{ $leastSoldProduct->total_sold }} fois</div>
                                @else
                                    <div class="text-muted small">Aucune vente enregistrée</div>
                                @endif
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-thermometer-empty fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtres & Recherche</h6>
            </div>
            <div class="card-body bg-light">
                <form action="{{ url('admin/products') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Recherche</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Nom, référence..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted">Catégorie</label>
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
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                            <a href="{{ url('admin/products') }}" class="btn btn-outline-secondary w-100">Reset</a>
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
                                <th class="ps-4">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        ID <i
                                            class="fas fa-sort{{ request('sort') == 'id' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        Produit <i
                                            class="fas fa-sort{{ request('sort') == 'name' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>
                                <th>Catégorie</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'quantity_stock', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        Stock <i
                                            class="fas fa-sort{{ request('sort') == 'quantity_stock' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'price', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        Prix <i
                                            class="fas fa-sort{{ request('sort') == 'price' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $product->id }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                                    </td>
                                    <td>
                                        @if ($product->category)
                                            <span
                                                class="badge bg-light text-dark border">{{ $product->category->name }}</span>
                                        @else
                                            <span class="text-muted small">Non classé</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($product->quantity_stock <= 0)
                                            <span class="badge bg-danger">Rupture</span>
                                        @elseif($product->quantity_stock <= ($product->alert_stock ?? 5))
                                            <span class="badge bg-warning text-dark">Faible
                                                ({{ $product->quantity_stock }})</span>
                                        @else
                                            <span class="badge bg-success">{{ $product->quantity_stock }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ number_format($product->price, 2) }} €</td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.products.show', $product->id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.products.destroy', $product->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Supprimer ce produit ?');">
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
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i><br>
                                        Aucun produit trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-3">
                <div class="d-flex justify-content-center">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
