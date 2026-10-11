@extends('layouts.app-back-office')

@section('title', 'Product Details : ' . $product->name)

@section('content')
    <div class="container-fluid py-4">
        {{-- Affichage des messages de session pour le feedback --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- En-tête avec informations de base --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1" id="display-name">{{ $product->name }}</h1>
                    <p class="text-muted mb-0">
                        category : <span id="display-category"
                            class="badge bg-info text-dark">{{ $product->category->name ?? 'N/A' }}</span> |
                        variant number : <span id="variant-count" class="fw-bold">{{ $variants->count() }}</span>
                    </p>
                    <div class="mt-2 small">
                        @forelse ($product->options ?? [] as $option)
                            <span class="badge bg-light text-dark border me-1">{{ $option['name'] }} : {{ implode(', ', $option['values']) }}</span>
                        @empty
                            <span class="text-muted">No options (single variant)</span>
                        @endforelse
                    </div>
                    @if (!empty($product->specs))
                        <div class="mt-1 small text-muted">
                            @foreach ($product->specs as $key => $value)
                                <span class="me-3"><strong>{{ $key }}</strong> : {{ $value }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#optionsModal">
                        <i class="fas fa-sliders-h me-1"></i> options &amp; specifications
                    </button>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal">
                        <i class="fas fa-edit me-1"></i> edit general information
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Graphique d'évolution du stock --}}
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Global Stock Evolution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="stockEvolutionChart" height="100"></canvas>
                    </div>
                </div>
                {{-- Table des Variantes --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Variants & Stocks</h5>
                        @php $canAddVariant = !empty($product->options) || $variants->isEmpty(); @endphp
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addVariantModal"
                            @disabled(!$canAddVariant) title="{{ $canAddVariant ? '' : 'Add an option first (options & specifications)' }}">
                            <i class="fas fa-plus me-1"></i> Add a variant
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variant</th>
                                    <th>Purchase Price (MGA)</th>
                                    {{-- Prix de vente --}}
                                    <th>Price (MGA)</th>
                                    {{-- Current stock --}}
                                    <th>Current Stock</th>
                                    <th>Alert Threshold</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="variants-table-body">
                                @forelse ($variants as $variant)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $variant->variant_name ?: 'Default' }}</div>
                                            <small class="text-muted">{{ $variant->sku }}</small>
                                        </td>
                                        <td>{{ number_format($variant->price_purchase, 2) }} MGA</td>
                                        <td>{{ number_format($variant->price, 2) }} MGA</td>
                                        <td><span
                                                class="badge {{ $variant->stock <= $variant->alert_stock ? 'bg-danger' : 'bg-success' }}">{{ $variant->stock }}</span>
                                        </td>
                                        <td>{{ $variant->alert_stock }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary edit-variant-btn"
                                                data-bs-toggle="modal" data-bs-target="#editVariantModal"
                                                data-id="{{ $variant->id }}"
                                                data-values="{{ json_encode($variant->option_values ?? new stdClass()) }}"
                                                data-brand-id="{{ $variant->brand_id }}"
                                                data-stock="{{ $variant->stock }}"
                                                data-alert-stock="{{ $variant->alert_stock }}"
                                                data-price="{{ $variant->price }}"
                                                data-price-purchase="{{ $variant->price_purchase }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.products.variants.destroy', $variant->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this variant?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No variants for this product.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Historique des mouvements --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Movement History</h5>
                        <select id="variant-filter" class="form-select form-select-sm w-50">
                            <option value="">All variants</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->variant_name ?: 'Default' }} ({{ $variant->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="history-list">
                            {{-- Loaded via AJAX --}}
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <button id="load-more-history" class="btn btn-sm btn-link">Load more</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edition Infos Générales --}}
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editProductForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $product->name }}"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $product->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Ajouter une Variante --}}
    <div class="modal fade" id="addVariantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.products.variants.store', $product->id) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add a new variant</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            @include('catalog::products._variant-identity', ['prefix' => 'add'])

                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Purchase
                                    Price</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-file-invoice-dollar text-muted"></i></span>
                                    <input type="number" name="price_purchase" class="form-control border-0 shadow-none"
                                        step="0.01" placeholder="0.00" required>
                                    <span class="input-group-text bg-light border-0 small">MGA</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Selling
                                    Price</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-tag text-muted"></i></span>
                                    <input type="number" name="price" class="form-control border-0 shadow-none"
                                        step="0.01" value="{{ $product->price }}" required>
                                    <span class="input-group-text bg-light border-0 small">MGA</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Initial
                                    Stock</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-cubes text-muted"></i></span>
                                    <input type="number" name="stock" class="form-control border-0 shadow-none"
                                        min="0" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Alert
                                    Threshold</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-bell text-warning"></i></span>
                                    <input type="number" name="alert_stock" class="form-control border-0 shadow-none"
                                        min="0" value="5">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-link text-muted text-decoration-none px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 rounded-pill shadow-sm">
                            <i class="fas fa-save me-2"></i>Save variant
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Modifier une Variante --}}
    <div class="modal fade" id="editVariantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form id="editVariantForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit variant</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            @include('catalog::products._variant-identity', ['prefix' => 'edit'])

                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Purchase
                                    Price</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-file-invoice-dollar text-muted"></i></span>
                                    <input type="number" name="price_purchase" id="edit-variant-price-purchase"
                                        class="form-control border-0 shadow-none" step="0.01" required>
                                    <span class="input-group-text bg-light border-0 small">MGA</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Selling
                                    Price</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-tag text-muted"></i></span>
                                    <input type="number" name="price" id="edit-variant-price"
                                        class="form-control border-0 shadow-none" step="0.01" required>
                                    <span class="input-group-text bg-light border-0 small">MGA</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Current
                                    Stock</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-cubes text-muted"></i></span>
                                    <input type="number" name="stock" id="edit-variant-stock"
                                        class="form-control border-0 shadow-none" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">Alert
                                    Threshold</label>
                                <div class="input-group border rounded-3 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-bell text-warning"></i></span>
                                    <input type="number" name="alert_stock" id="edit-variant-alert"
                                        class="form-control border-0 shadow-none" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-link text-muted text-decoration-none px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                            <i class="fas fa-check me-2"></i>Apply changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Options & spécifications --}}
    <div class="modal fade" id="optionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.products.options.update', $product->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Options & specifications</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="fw-bold">Options</h6>
                        <p class="small text-muted">
                            Values used by a variant cannot be removed. New values can be added here or directly when adding a variant.
                        </p>
                        <div id="product-option-rows">
                            @foreach ($product->options ?? [] as $i => $option)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="options[{{ $i }}][name]" class="form-control" value="{{ $option['name'] }}" maxlength="50" placeholder="Option (e.g. Volume)"></div>
                                    <div class="col-md-7"><input type="text" name="options[{{ $i }}][values]" class="form-control" value="{{ implode(', ', $option['values']) }}" placeholder="Values, comma separated"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-editable-row" title="Remove"><i class="fas fa-trash"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-4 add-editable-row"
                            data-target="product-option-rows" data-prefix="options" data-first="name" data-second="values"
                            data-first-placeholder="Option (e.g. Volume)" data-second-placeholder="Values, comma separated">
                            <i class="fas fa-plus"></i> Add an option
                        </button>

                        <h6 class="fw-bold">Specifications</h6>
                        <div id="product-spec-rows">
                            @foreach ($product->specs ?? [] as $key => $value)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="specs[{{ $loop->index }}][key]" class="form-control" value="{{ $key }}" maxlength="100"></div>
                                    <div class="col-md-7"><input type="text" name="specs[{{ $loop->index }}][value]" class="form-control" value="{{ $value }}"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-editable-row" title="Remove"><i class="fas fa-trash"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary add-editable-row"
                            data-target="product-spec-rows" data-prefix="specs" data-first="key" data-second="value"
                            data-first-placeholder="e.g. Origin" data-second-placeholder="e.g. China">
                            <i class="fas fa-plus"></i> Add a specification
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Lignes ajoutables / supprimables (options et spécifications) : construites avec le DOM, sans HTML injecté
        (function () {
            let counter = 1000;
            document.addEventListener('click', function (event) {
                const add = event.target.closest('.add-editable-row');
                if (add) {
                    const i = counter++;
                    const input = (field, placeholder) => {
                        const node = document.createElement('input');
                        node.type = 'text';
                        node.className = 'form-control';
                        node.name = `${add.dataset.prefix}[${i}][${field}]`;
                        node.placeholder = placeholder;
                        return node;
                    };
                    const col = (className, child) => { const div = document.createElement('div'); div.className = className; div.appendChild(child); return div; };
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-outline-danger w-100 remove-editable-row';
                    remove.title = 'Remove';
                    remove.textContent = '×';
                    const row = document.createElement('div');
                    row.className = 'row g-2 mb-2 editable-row';
                    row.append(
                        col('col-md-4', input(add.dataset.first, add.dataset.firstPlaceholder)),
                        col('col-md-7', input(add.dataset.second, add.dataset.secondPlaceholder)),
                        col('col-md-1', remove)
                    );
                    document.getElementById(add.dataset.target).appendChild(row);
                    row.querySelector('input').focus();
                    return;
                }
                const remove = event.target.closest('.remove-editable-row');
                if (remove) {
                    remove.closest('.editable-row').remove();
                }
            });
        })();
    </script>
@endsection


@push('scripts')
    <script>
        const PRODUCT_ID = {{ $product->id }};
        let stockChart = null;

        // Attendre que jQuery soit disponible
        if (typeof jQuery !== 'undefined') {
            initProductPage();
        } else {
            const checkJQuery = setInterval(() => {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJQuery);
                    initProductPage();
                }
            }, 100);
        }

        function initProductPage() {
            const $ = window.jQuery;
            $(document).ready(function() {
                loadHistory();
                loadChart();

                // Gestion de l'édition générale AJAX
                $('#editProductForm').on('submit', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: `/admin/products/main/${PRODUCT_ID}/update-details`,
                        method: 'PATCH',
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                $('#display-name').text(res.data.name);
                                $('#display-category').text(res.data.category_name);
                                // Fermer le modal
                                const modal = bootstrap.Modal.getInstance(document
                                    .getElementById('editProductModal'));
                                modal.hide();
                            }
                        }
                    });
                });

                // Gestion de l'ouverture du modal de modification de variante
                $('.edit-variant-btn').on('click', function() {
                    const id = $(this).data('id');
                    const values = JSON.parse(this.dataset.values || '{}');
                    const stock = $(this).data('stock');
                    const alert = $(this).data('alert-stock');
                    const price = $(this).data('price');
                    const pricePurchase = $(this).data('price-purchase');

                    // Remplissage des champs du formulaire
                    document.querySelectorAll('#editVariantForm .variant-option-input').forEach(input => {
                        input.value = values[input.dataset.optionName] ?? '';
                    });
                    document.querySelector('#editVariantForm .variant-brand-select').value = this.dataset.brandId || '';
                    $('#edit-variant-stock').val(stock);
                    $('#edit-variant-alert').val(alert);
                    $('#edit-variant-price').val(price);
                    $('#edit-variant-price-purchase').val(pricePurchase);
                    $('#editVariantForm').attr('action', `/admin/products/variants/${id}`);
                });

                // Filtre d'historique
                $('#variant-filter').on('change', function() {
                    loadHistory($(this).val());
                });
            });

            // Motif du mouvement et nom de couleur sont des saisies : on les échappe avant de les insérer dans le HTML
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            function loadHistory(variantId = '') {
                const $list = $('#history-list');
                $list.html('<li class="list-group-item text-center">Loading...</li>');

                $.getJSON(`/api/products/${PRODUCT_ID}/movements?variant_id=${variantId}`)
                    .done(function(res) {
                        const html = res.data.map(m => `
                            <li class="list-group-item border-0 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <small class="fw-bold text-uppercase">${m.type === 'in' ? 'In' : 'Out'}</small>
                                    <small class="text-muted">${new Date(m.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="small">${escapeHtml(m.variant_label)} : <strong>${m.quantity} units</strong></div>
                                <div class="text-muted extra-small">${escapeHtml(m.reason)}</div>
                            </li>
                        `).join('');
                        $list.html(html ||
                            '<li class="list-group-item text-center text-muted">No movements found</li>');
                    })
                    .fail(function() {
                        $list.html(
                            '<li class="list-group-item text-center text-danger small">Loading error</li>');
                    });
            }

            function loadVariants() {
                // Cette fonction peut être utilisée pour recharger les variantes après une modification
            }

            function loadChart() {
                $.getJSON(`/api/products/${PRODUCT_ID}/stock-evolution`)
                    .done(function(res) {
                        if (res.success) renderChart(res.data);
                    })
                    .fail(function(err) {
                        console.error('Erreur graphique:', err);
                    });
            }

            function renderChart(data) {
                const chartData = Array.isArray(data) ? data : Object.values(data).flat();
                const ctx = $('#stockEvolutionChart')[0].getContext('2d');

                if (stockChart) {
                    stockChart.data.labels = chartData.map(item => item.x);
                    stockChart.data.datasets[0].data = chartData.map(item => item.y);
                    stockChart.update();
                    return;
                }

                stockChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(item => item.x),
                        datasets: [{
                            label: 'Total Stock',
                            data: chartData.map(item => item.y),
                            fill: false,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                },
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Quantity in stock'
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
@endpush
