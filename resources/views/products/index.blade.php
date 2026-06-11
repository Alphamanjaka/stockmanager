@extends('layouts.app-back-office')

@section('title', 'Products Management')

@section('actions')
    <div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Product
        </a>
        <a href="{{ route('admin.products.exportPdf') }}" class="btn btn-success">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
@endsection

@section('content')
        {{-- KPI Section --}}
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow h-100 py-2">
                    <div class="card-body">
                        {{-- Most Sold Product --}}
                        {{-- add link to product detail page --}}
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Top Selling</div>
                                @if ($mostSoldProduct && $mostSoldProduct->productColor)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <a href="{{ route('admin.products.show', $mostSoldProduct->productColor->product->id) }}"
                                            class="text-decoration-none">
                                            {{ $mostSoldProduct->productColor->toString() }}
                                        </a>
                                    </div>
                                    <div class="small text-muted">Sold {{ $mostSoldProduct->total_quantity }} times</div>
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
                        {{-- Least Sold Product --}}
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Least Sold</div>
                                @if ($leastSoldProduct && $leastSoldProduct->productColor)
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <a href="{{ route('admin.products.show', $leastSoldProduct->productColor->product->id) }}"
                                            class="text-decoration-none">
                                            {{ $leastSoldProduct->productColor->toString() }}
                                        </a>
                                    </div>
                                    <div class="small text-muted">Sold {{ $leastSoldProduct->total_quantity }} times</div>
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
        <div class="card shadow mb-2">
            <div class="card-header py-2">
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
                                <option value="{{ $cat->id }}"
                                    {{ request('category') == $cat->id ? 'selected' : '' }}>
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
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'product_id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        Product <i
                                            class="fas fa-sort{{ request('sort') == 'product_id' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>
                                <th>
                                    Category
                                </th>

                                {{-- stats des produits dispo ou en rupture ou en commande --}}
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}"
                                        class="text-dark text-decoration-none">
                                        Stock <i
                                            class="fas fa-sort{{ request('sort') == 'stock' ? (request('order') == 'asc' ? '-up' : '-down') : '' }} small text-muted"></i>
                                    </a>
                                </th>


                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productVariants as $item)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $item->id }}</td>
                                    <td>
                                        <div class="fw-bold text-dark"> <a
                                                href="{{ route('admin.products.show', $item->product->id) }}">{{ $item->toString() ?? 'N/A' }}</a>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark border">{{ $item->getCategoryNameAttribute() ?? 'N/A' }}</span>

                                    </td>
                                    <td class="text pe-4">
                                        {{ $item->stock }} {{ $item->stock <= 5 ? ' (Low Stock)' : '' }}
                                    </td>

                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.products.show', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $item->id) }}"
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
                    {{ $productVariants->links() }}
                </div>
            </div>
        </div>
@endsection
