@extends('layouts.app-back-office')

@section('title', 'Add New Product')

@section('content')
    <form action="{{ route('admin.products.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-box-seam me-2"></i>
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select name="category_id" id="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">Select a category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">General Price (MGA)</label>
                                <input type="number" step="0.01" name="price" required id="price"
                                    class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea name="description" id="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Variants & Stock Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-palette me-2"></i>
                            <h5 class="mb-0">Product Variants (Colors & Stock)</h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-light" id="addColor">
                            <i class="bi bi-plus-lg"></i> Add Variant
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Color Name</th>
                                        <th>Initial Stock</th>
                                        <th>Alert Threshold</th>
                                        <th>Price (MGA)</th> 
                                        <th>Cost Price (MGA)</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="colorStockTable">
                                    <tr>
                                        <td class="ps-3">
                                            <input type="text" name="colors[]" class="form-control"
                                                placeholder="e.g. Red" list="existingColors" required>
                                        </td>
                                        <td><input type="number" name="stocks[]" class="form-control" placeholder="0"
                                                min="0" required></td>
                                        <td><input type="number" name="alert_stocks[]" class="form-control" placeholder="5"
                                                min="0"></td>
                                        <td><input type="number" name="prices[]" class="form-control" placeholder="0.00"
                                                min="0" step="0.01"></td>
                                        <td><input type="number" name="price_purchases[]" class="form-control" placeholder="0.00"
                                                min="0" step="0.01"></td>
                                        <td class="text-center"><button type="button"
                                                class="btn btn-outline-danger btn-sm removeColor"><i
                                                    class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Datalist for existing color suggestions --}}
                <datalist id="existingColors">
                    @foreach ($colors ?? [] as $color)
                        <option value="{{ $color->name }}">
                    @endforeach
                </datalist>

                <div class="d-flex justify-content-start gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-save me-2"></i> Save Product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i> Creation Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item border-0 px-0"><i
                                    class="bi bi-check2-circle text-success me-2"></i>
                                Use clear names.</li>
                            <li class="list-group-item border-0 px-0"><i
                                    class="bi bi-check2-circle text-success me-2"></i>
                                Assign a category.</li>
                            <li class="list-group-item border-0 px-0"><i
                                    class="bi bi-check2-circle text-success me-2"></i>
                                Add color variants.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('addColor').addEventListener('click', function() {
            const tableBody = document.getElementById('colorStockTable');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="ps-3"><input type="text" name="colors[]" class="form-control" placeholder="Color Name" list="existingColors" required></td>
                <td><input type="number" name="stocks[]" class="form-control" placeholder="0" min="0" required></td>
                <td><input type="number" name="alert_stocks[]" class="form-control" placeholder="5" min="0"></td>
                <td><input type="number" name="prices[]" class="form-control" placeholder="0.00" min="0" step="0.01"></td>
                <td><input type="number" name="price_purchases[]" class="form-control" placeholder="0.00" min="0" step="0.01"></td>
                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm removeColor"><i class="bi bi-trash"></i></button></td>
            `;
            tableBody.appendChild(newRow);
        });

        document.getElementById('colorStockTable').addEventListener('click', function(e) {
            if (e.target.closest('.removeColor')) {
                e.target.closest('tr').remove();
            }
        });
    </script>
@endpush
