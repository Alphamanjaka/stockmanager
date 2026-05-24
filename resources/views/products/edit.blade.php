@extends('layouts.app-back-office')
@section('title', 'Edit Product : ' . $item->toString())

@section('content')
    <form action="{{ route('admin.products.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-7">
                {{-- Basic Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-box-seam me-2"></i>
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $item->toString()) }}" required readonly>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">General Price (MGA)</label>
                                <input type="number" step="0.01" name="price" id="price"
                                    class="form-control @error('price') is-invalid @enderror"
                                    value="{{ old('price', $item->price) }}" readonly>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea name="description" id="description" rows="3"
                                class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->product->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Product Variant Details --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-palette me-2"></i>
                        <h5 class="mb-0">Product Variant</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantity_stock" class="form-label">Current Stock <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="quantity_stock" id="quantity_stock"
                                    class="form-control @error('quantity_stock') is-invalid @enderror"
                                    value="{{ old('quantity_stock', $item->stock) }}" required readonly>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> For precise tracking, use stock movements.
                                </div>
                                @error('quantity_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="alert_stock" class="form-label">Alert Threshold</label>
                                <input type="number" name="alert_stock" id="alert_stock"
                                    class="form-control @error('alert_stock') is-invalid @enderror"
                                    value="{{ old('alert_stock', $product->alert_stock ?? 10) }}">
                                <div class="form-text">Minimum quantity before alert is triggered.</div>
                                @error('alert_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- price of productColor --}}
                            <div class="col-md-4 mb-3">
                                <label for="label_price" class="form-label">Current Price <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="label_price" id="label_price"
                                    class="form-control @error('label_price') is-invalid @enderror"
                                    value="{{ old('label_price', $item->price) }}" required>
                                <div class="form-text">Enter the current price for this product variant.</div>
                                @error('label_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-save me-2"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>

            <div class="col-lg-5">
                {{-- category representation: searchbar handled by javascript code and radio --}}
                {{-- add button on the header to create a new category --}}

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="bi bi-tags me-2"></i>
                        <h5 class="mb-0">Category</h5>
                        <button type="button" class="btn btn-sm btn-light ms-auto" data-bs-toggle="modal"
                            data-bs-target="#createCategoryModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="category_search" class="form-label">Search Categories</label>
                            <input type="text" id="category_search" class="form-control" placeholder="Type to search...">
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($categories as $category)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category_id"
                                        id="category_{{ $category->id }}" value="{{ $category->id }}"
                                        {{ old('category_id', $item->product->category_id) == $category->id ? 'checked' : '' }}
                                        required>
                                    <label class="form-check-label" for="category_{{ $category->id }}">
                                        {{ $category->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('category_id')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                {{-- color representation: searchbar handled by javascript code and radios --}}
                {{-- add button on the header to create a new color variant --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white d-flex align-items-center">
                        <i class="bi bi-palette me-2"></i>
                        <h5 class="mb-0">Color Variants</h5>
                        <button type="button" class="btn btn-sm btn-light ms-auto" data-bs-toggle="modal"
                            data-bs-target="#createColorModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Color
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="color_search" class="form-label">Search Colors</label>
                            <input type="text" id="color_search" class="form-control"
                                placeholder="Type to search...">
                        </div>
                        <div class="d-flex flex-wrap gap-3" id="color-options">
                            @foreach ($colors as $color)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_id"
                                        id="color_{{ $color->id }}" value="{{ $color->id }}"
                                        {{ old('color_id', $item->color_id) == $color->id ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="color_{{ $color->id }}">
                                        {{ $color->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Current Information</h6>
                        <div class="small text-muted">
                            <p><strong>Created:</strong> {{ $item->product->created_at->format('d/m/Y H:i') }}</p>
                            <p class="mb-0"><strong>Last Updated:</strong>
                                {{ $item->product->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
