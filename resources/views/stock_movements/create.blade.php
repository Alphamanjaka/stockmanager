@extends('layouts.app-back-office')
@section('title', 'Create Stock Movement')
@section('content')
    <div class="container mt-4">
        <h1 class="mb-4">Create Stock Movement</h1>

        <div class="row">
            <!-- Left Column: Product Selection -->
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Select Product Variant</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-product" class="form-control"
                                    placeholder="Search product or color...">
                            </div>
                        </div>

                        <div id="product-list-container" style="max-height: 400px; overflow-y: auto;">
                            <div class="list-group" id="product-results">
                                @foreach ($productColors as $item)
                                    <button type="button" class="list-group-item list-group-item-action product-item"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->product->name }} - {{ $item->color->name }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $item->product->name }}</span>
                                            <span class="badge bg-secondary rounded-pill">{{ $item->color->code }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Movement Details -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Movement Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.movements.store') }}" method="POST" id="movement-form">
                            @csrf
                            <input type="hidden" name="product_color_id" id="selected-product-id" required>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Selected Product</label>
                                <div id="selected-product-display" class="alert alert-info py-2">
                                    Please select a product from the list on the left.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Quantity</label>
                                <input type="number" name="quantity" class="form-control form-control-lg" min="1"
                                    placeholder="Enter amount" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Movement Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="" selected disabled>Choose type...</option>
                                    <option value="in">In (Stock Increase)</option>
                                    <option value="out">Out (Stock Decrease)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Reason</label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="Optional: damage, return, adjustment..."
                                    maxlength="255"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                                    <i class="fas fa-check-circle me-2"></i>Validate Movement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search logic
            $('#search-product').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $("#product-results .product-item").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Selection logic
            $('.product-item').on('click', function() {
                $('.product-item').removeClass('active');
                $(this).addClass('active');

                let id = $(this).data('id');
                let name = $(this).data('name');

                $('#selected-product-id').val(id);
                $('#selected-product-display').removeClass('alert-info').addClass('alert-success').text(
                    name);
                $('#submit-btn').prop('disabled', false);
            });
        });
    </script>
@endpush
