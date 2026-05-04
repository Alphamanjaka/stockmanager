@extends('layouts.app-back-office')
@section('title', 'Detail of Product : ' . $item->product->name . ' ' . $item->color->name)
@section('styles')
@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to list
            </a>
            <div>
                <a href="{{ route('admin.products.edit', $item->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Product Details Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100" id="product-details-card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-box"></i> Product Information</h5>
                        <button class="btn btn-sm btn-outline-light float-end edit-button" data-section="details">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush static-view">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Name:</strong>
                                <span id="product-name-display">{{ $item->product->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Color:</strong>
                                <span id="product-color-display">{{ $item->color->name ?? 'Not defined' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Category:</strong>
                                <span
                                    id="product-category-display">{{ $item->product->category->name ?? 'Not defined' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Selling Price:</strong>
                                <span class="fw-bold text-success">{{ number_format($item->price, 2) }} MGA</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Description:</strong>
                                <p class="mt-2 text-muted" id="product-description-display">
                                    {{ $item->product->description ?? 'No description available' }}</p>
                            </li>
                        </ul>

                        {{-- Edit Form for Product Details --}}
                        <form class="edit-form d-none" data-section="details">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="edit-name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit-name" name="name"
                                    value="{{ $item->product->name }}">
                                <div class="invalid-feedback" id="edit-name-error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit-category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit-category_id" name="category_id">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ $item->product->category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="edit-category_id-error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit-description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit-description" name="description" rows="3">{{ $item->product->description }}</textarea>
                                <div class="invalid-feedback" id="edit-description-error"></div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary cancel-edit"
                                    data-section="details">Cancel</button>
                                <button type="submit" class="btn btn-primary save-edit"
                                    data-section="details">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Stock Details Card --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100" id="stock-status-card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-warehouse"></i> Stock Status</h5>
                        <button class="btn btn-sm btn-outline-light float-end edit-button" data-section="stock">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush static-view">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Current Stock:</strong>
                                <span
                                    class="badge fs-6 {{ $item->stock <= $item->alert_stock ? 'bg-danger' : 'bg-success' }}"
                                    id="current-stock-display">
                                    {{ $item->stock }} units
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Alert Threshold:</strong>
                                <span id="alert-stock-display">{{ $item->alert_stock }} units</span>
                            </li>
                        </ul>

                        {{-- Edit Form for Stock Status --}}
                        <form class="edit-form d-none" data-section="stock">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="edit-stock" class="form-label">Current Stock</label>
                                <input type="number" class="form-control" id="edit-stock" name="stock"
                                    value="{{ $item->stock }}">
                                <div class="invalid-feedback" id="edit-stock-error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit-alert_stock" class="form-label">Alert Threshold</label>
                                <input type="number" class="form-control" id="edit-alert_stock" name="alert_stock"
                                    value="{{ $item->alert_stock }}">
                                <div class="invalid-feedback" id="edit-alert_stock-error"></div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary cancel-edit"
                                    data-section="stock">Cancel</button>
                                <button type="submit" class="btn btn-primary save-edit"
                                    data-section="stock">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Selling Price Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100" id="selling-price-card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> Selling Price</h5>
                        <button class="btn btn-sm btn-outline-light float-end edit-button" data-section="price">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush static-view">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Selling Price:</strong>
                                <span class="fw-bold text-success"
                                    id="selling-price-display">{{ number_format($item->price, 2) }} MGA</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Created on:</strong>
                                <span>{{ $item->product->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Last Updated:</strong>
                                <span>{{ $item->product->updated_at->format('d/m/Y H:i') }}</span>
                            </li>
                        </ul>

                        {{-- Edit Form for Selling Price --}}
                        <form class="edit-form d-none" data-section="price">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="edit-price" class="form-label">Selling Price (MGA)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="edit-price"
                                        name="price" value="{{ $item->price }}">
                                    <span class="input-group-text">MGA</span>
                                    <div class="invalid-feedback" id="edit-price-error"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary cancel-edit"
                                    data-section="price">Cancel</button>
                                <button type="submit" class="btn btn-primary save-edit"
                                    data-section="price">Save</button>
                            </div>
                            </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock Evolution Chart --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Stock Evolution</h5>
            </div>
            <div class="card-body" style="position: relative; height: 300px; width: 100%;">
                <canvas id="stockChart"></canvas>
            </div>
        </div>

        {{-- Stock Movements History --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-arrows-alt"></i> Stock Movements History</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{!! $movement->type === 'in'
                                    ? '<span class="badge bg-success">Entry</span>'
                                    : '<span class="badge bg-danger">Exit</span>' !!}</td>
                                <td class="fw-bold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity }}</td>
                                <td>{{ $movement->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No stock movements recorded for this product.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3 d-flex justify-content-center">{{ $stockMovements->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script type="module">
        $(function() {
            const ctx = document.getElementById('stockChart').getContext('2d');
            const alertThreshold =
                {!! $item->alert_stock !!}; // On pourrait passer cette valeur depuis le PHP ($product->min_stock)

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! $chartLabels !!},
                    datasets: [{
                        label: 'Niveau du stock',
                        data: {!! $chartData !!},
                        borderColor: '#17a2b8',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const {
                                ctx,
                                chartArea
                            } = chart;
                            if (!chartArea) return null;

                            // Dégradé qui change de couleur si on passe sous le seuil
                            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0,
                                chartArea.top);
                            gradient.addColorStop(0, 'rgba(220, 53, 69, 0.2)'); // Rouge en bas
                            gradient.addColorStop(alertThreshold / 100,
                                'rgba(23, 162, 184, 0.2)'); // Transition
                            return gradient;
                        },
                        borderWidth: 3,
                        stepped: true,
                        fill: true,
                        pointRadius: (context) => context.raw === 0 ? 6 :
                        3, // Point plus gros si stock à zéro
                        pointBackgroundColor: (context) => context.raw < alertThreshold ?
                            '#dc3545' : '#17a2b8'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        annotation: {
                            annotations: {
                                line1: {
                                    type: 'line',
                                    yMin: alertThreshold,
                                    yMax: alertThreshold,
                                    borderColor: 'rgba(220, 53, 69, 0.8)',
                                    borderWidth: 2,
                                    borderDash: [6, 6],
                                    label: {
                                        display: true,
                                        content: 'Seuil d\'alerte',
                                        position: 'end'
                                    }
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` Quantité : ${context.parsed.y} unités`,
                                footer: (context) => {
                                    if (context[0].parsed.y < alertThreshold)
                                        return '⚠️ Stock critique !';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: alertThreshold + 20 // Pour toujours voir le seuil
                        }
                    }
                }
            });
        });

        $(document).ready(function() {
            // Cache categories for display update
            const categories = @json($categories->pluck('name', 'id'));

            // Function to toggle between static view and edit form
            function toggleEditMode(section, isEditing) {
                const card = $(`#${section}-card`);
                card.find('.static-view').toggleClass('d-none', isEditing);
                card.find('.edit-form').toggleClass('d-none', !isEditing);
                card.find('.edit-button').toggleClass('d-none', isEditing);
            }

            // Handle Edit button click
            $('.edit-button').on('click', function() {
                const section = $(this).data('section');
                toggleEditMode(section, true);
            });

            // Handle Cancel button click
            $('.cancel-edit').on('click', function() {
                const section = $(this).data('section');
                toggleEditMode(section, false);
                // Reset form fields and errors
                const form = $(this).closest('form');
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
                // Optionally, reset form values to original if needed
                // For simplicity, we're not doing a full reset here, just hiding the form.
            });

            // Handle form submission for Product Details
            $('#product-details-card .edit-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const url = "{{ route('admin.products.updateDetails', $item->product_id) }}";
                const formData = form.serialize();

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success');
                            $('#product-name-display').text(response.data.name);
                            $('#product-description-display').text(response.data.description);
                            $('#product-category-display').text(response.data.category_name);
                            toggleEditMode('product-details', false);
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        handleAjaxError(xhr, form);
                    }
                });
            });

            // Handle form submission for Stock Status
            $('#stock-status-card .edit-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const url = "{{ route('admin.product-colors.updateStock', $item->id) }}";
                const formData = form.serialize();

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success');
                            $('#current-stock-display').text(response.data.stock + ' units')
                                .removeClass('bg-danger bg-success').addClass(response.data
                                    .stock <= response.data.alert_stock ? 'bg-danger' :
                                    'bg-success');
                            $('#alert-stock-display').text(response.data.alert_stock +
                                ' units');
                            toggleEditMode('stock-status', false);
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        handleAjaxError(xhr, form);
                    }
                });
            });

            // Handle form submission for Selling Price
            $('#selling-price-card .edit-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const url = "{{ route('admin.product-colors.updatePrice', $item->id) }}";
                const formData = form.serialize();

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    data: formData,
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success');
                        $('#selling-price-display').text(parseFloat(response.data.price)
                            .toLocaleString('fr-MG', {
                                style: 'currency',
                                currency: 'MGA'
                            }));
                        toggleEditMode('selling-price', false);
                    },
                    error: function(xhr) {
                        handleAjaxError(xhr, form);
                    }
                });
            });

            // Generic AJAX error handler
            function handleAjaxError(xhr, form) {
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');

                if (xhr.status === 422) { // Validation errors
                    const errors = xhr.responseJSON.errors;
                    for (const field in errors) {
                        const input = form.find(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(errors[field][0]);
                    }
                    Swal.fire('Validation Error!', 'Please check your input.', 'error');
                } else {
                    Swal.fire('Error!', xhr.responseJSON.message || 'An unexpected error occurred.', 'error');
                }
            }
        });
    </script>
@endpush
