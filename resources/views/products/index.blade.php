@extends('layouts.app-back-office')

@section('title', 'Products Management')

@section('content')
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800">Products</h1>
            <div>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Product
                </a>
                <a href="{{ route('admin.products.exportPdf') }}" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>

        </div>

        {{-- KPI Section --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Top Selling</div>
                                @if ($mostSoldProduct && $mostSoldProduct->product)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $mostSoldProduct->product->name }}</div>
                                    <div class="small text-muted">Sold {{ $mostSoldProduct->total_sold }} times</div>
                                @else
                                    <div class="text-muted small">No sales recorded</div>
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
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Least Sold</div>
                                @if ($leastSoldProduct && $leastSoldProduct->product)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $leastSoldProduct->product->name }}</div>
                                    <div class="small text-muted">Sold {{ $leastSoldProduct->total_sold }} times</div>
                                @else
                                    <div class="text-muted small">No sales recorded</div>
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
                <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
            </div>
            <div class="card-body bg-light">
                <form action="{{ url('admin/products') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Name, reference..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                                <th>Couleur</th>
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
                            @forelse ($products as $item)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $item->id }}</td>
                                    <td>
                                        <div class="fw-bold text-dark"> <a
                                                href="{{ route('admin.products.show', $item->id) }}">{{ $item->product->name }}</a>
                                        </div>
                                    </td>
                                    <td>{{ $item->color->name ?? 'N/A' }}</td>
                                    <td>
                                        @if ($item->product->category)
                                            <span
                                                class="badge bg-light text-dark border">{{ $item->product->category->name }}</span>
                                        @else
                                            <span class="text-muted small">Unassigned</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($item->stock <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($item->stock <= ($item->alert_stock ?? 5))
                                            <span class="badge bg-warning text-dark">Low Stock</span>
                                            ({{ $item->stock }})
                                            </span>
                                        @else
                                            <span class="badge bg-success">{{ $item->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ number_format($item->product->price, 2) }} MGA </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.products.show', $item->product->id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $item->product->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.products.destroy', $item->product->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this product ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="delete">
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
                                        No product found.
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
