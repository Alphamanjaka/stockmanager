@extends('layouts.app-back-office')
@section('title', 'Edit Product : ' . $product->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
        </div>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Product</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Colonne Gauche : Infos Générales --}}
                        <div class="col-md-8">
                            <h6 class="text-muted mb-3">General Information</h6>

                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $product->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span
                                        class="text-danger">*</span></label>
                                <select name="category_id" id="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">Choose a category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="5"
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Colonne Droite : Stock et Prix --}}
                        <div class="col-md-4">
                            <h6 class="text-muted mb-3">Stock & Pricing</h6>
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Selling Price (Mga) <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="price" id="price"
                                                class="form-control @error('price') is-invalid @enderror"
                                                value="{{ old('price', $product->price) }}" required>
                                            <span class="input-group-text">Mga</span>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="quantity_stock" class="form-label">Current Stock <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="quantity_stock" id="quantity_stock"
                                            class="form-control @error('quantity_stock') is-invalid @enderror"
                                            value="{{ old('quantity_stock', $product->quantity_stock) }}" required>
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle"></i> For precise tracking, prefer stock movements.
                                        </div>
                                        @error('quantity_stock')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="alert_stock" class="form-label">Alert Threshold <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="alert_stock" id="alert_stock"
                                            class="form-control @error('alert_stock') is-invalid @enderror"
                                            value="{{ old('alert_stock', $product->alert_stock ?? 10) }}">
                                        <div class="form-text">Minimum quantity before alert.</div>
                                        @error('alert_stock')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
