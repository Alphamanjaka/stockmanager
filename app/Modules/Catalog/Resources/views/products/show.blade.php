@extends('layouts.app-back-office')

@section('title', $product->name)

@section('subtitle')
    <span id="display-category">{{ $product->category->name ?? 'N/A' }}</span>
    · <span id="variant-count">{{ $variants->count() }}</span> variant(s)
@endsection

@section('actions')
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#optionsModal">
        <i class="bi bi-sliders" aria-hidden="true"></i> Options &amp; specifications
    </button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProductModal">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </button>
@endsection

@php $canAddVariant = !empty($product->options) || $variants->isEmpty(); @endphp

@section('content')
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="ui-stack">
                <x-ui.card title="Details">
                    <dl class="ui-dl">
                        <dt>Options</dt>
                        <dd class="d-flex flex-wrap gap-1">
                            @forelse ($product->options ?? [] as $option)
                                <span class="ui-tag">{{ $option['name'] }} : {{ implode(', ', $option['values']) }}</span>
                            @empty
                                <span class="text-muted">No options (single variant)</span>
                            @endforelse
                        </dd>
                        @foreach ($product->specs ?? [] as $key => $value)
                            <dt>{{ $key }}</dt>
                            <dd>{{ $value }}</dd>
                        @endforeach
                        <dt>Description</dt>
                        <dd class="text-prewrap" id="display-description">{{ $product->description ?: '—' }}</dd>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Variants & stock" flush
                    subtitle="Stock is read-only here: it changes with received purchases, sales, stock counts and corrections.">
                    <x-slot:actions>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addVariantModal"
                            @disabled(!$canAddVariant) title="{{ $canAddVariant ? '' : 'Add an option first (options & specifications)' }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add a variant
                        </button>
                    </x-slot:actions>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Variant</th>
                                    <th class="text-end">Purchase price</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Alert at</th>
                                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody id="variants-table-body">
                                @forelse ($variants as $variant)
                                    <tr>
                                        <td>
                                            <div class="ui-cell-title">{{ $variant->variant_name ?: 'Default' }}</div>
                                            <div class="ui-cell-meta">{{ $variant->sku }}</div>
                                        </td>
                                        <td class="text-end">{{ money($variant->price_purchase) }}</td>
                                        <td class="text-end fw-semibold">{{ money($variant->price) }}</td>
                                        <td class="text-end">
                                            <x-ui.badge :tone="$variant->stock <= $variant->alert_stock ? 'danger' : 'success'" dot>{{ $variant->stock }}</x-ui.badge>
                                        </td>
                                        <td class="text-end text-muted">{{ $variant->alert_stock }}</td>
                                        <td class="text-end">
                                            <div class="ui-row-actions">
                                                <button type="button" class="btn btn-icon edit-variant-btn" title="Edit" aria-label="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editVariantModal"
                                                    data-id="{{ $variant->id }}"
                                                    data-values="{{ json_encode($variant->option_values ?? new stdClass()) }}"
                                                    data-brand-id="{{ $variant->brand_id }}"
                                                    data-stock="{{ $variant->stock }}"
                                                    data-alert-stock="{{ $variant->alert_stock }}"
                                                    data-price="{{ $variant->price }}"
                                                    data-price-purchase="{{ $variant->price_purchase }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                {{-- Secours en cas d'erreur de saisie : correction par un mouvement de stock --}}
                                                @if (Route::has('admin.movements.create'))
                                                    <a href="{{ route('admin.movements.create', ['variant' => $variant->id]) }}" class="btn btn-icon"
                                                        title="Stock correction" aria-label="Stock correction">
                                                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                <x-ui.delete-button :action="route('admin.products.variants.destroy', $variant->id)"
                                                    confirm="Delete this variant?" />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-ui.empty-row :colspan="6" icon="bi-box">No variant for this product.</x-ui.empty-row>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card title="Stock evolution" subtitle="Total stock of all variants">
                    <div style="height: 260px;">
                        <canvas id="stockEvolutionChart" role="img" aria-label="Stock evolution"></canvas>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="col-xl-4">
            <x-ui.card title="Movement history" flush>
                <x-slot:toolbar>
                    <select id="variant-filter" class="form-select form-select-sm" aria-label="Variant">
                        <option value="">All variants</option>
                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->variant_name ?: 'Default' }} ({{ $variant->sku }})</option>
                        @endforeach
                    </select>
                </x-slot:toolbar>

                <ul class="ui-feed" id="history-list">
                    {{-- Chargé en AJAX --}}
                </ul>
            </x-ui.card>
        </div>
    </div>

    {{-- Modifier les informations générales --}}
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editProductForm" class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="editProductModalTitle">Edit product</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <x-ui.field name="name" id="edit-product-name" label="Product name" required :value="$product->name" />
                    <x-ui.field name="category_id" label="Category">
                        <select name="category_id" id="category_id" class="form-select">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($product->category_id == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field class="mb-0" name="description" id="edit-product-description" label="Description" type="textarea"
                        rows="3" :value="$product->description" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ajouter une variante --}}
    <div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.products.variants.store', $product->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title" id="addVariantModalTitle">New variant</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @include('catalog::products._variant-identity', ['prefix' => 'add'])
                        @include('catalog::products._variant-figures', ['prefix' => 'add', 'variant' => null])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save variant</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modifier une variante --}}
    <div class="modal fade" id="editVariantModal" tabindex="-1" aria-labelledby="editVariantModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editVariantForm" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h2 class="modal-title" id="editVariantModalTitle">Edit variant</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @include('catalog::products._variant-identity', ['prefix' => 'edit'])
                        @include('catalog::products._variant-figures', ['prefix' => 'edit'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Apply changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Options et spécifications --}}
    <div class="modal fade" id="optionsModal" tabindex="-1" aria-labelledby="optionsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.products.options.update', $product->id) }}" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h2 class="modal-title" id="optionsModalTitle">Options &amp; specifications</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ui-form-section">
                        <h3 class="ui-form-section-title">Options</h3>
                        <p class="ui-form-section-hint">
                            Values used by a variant cannot be removed. New values can be added here or directly when adding a variant.
                        </p>
                        <div id="product-option-rows">
                            @foreach ($product->options ?? [] as $i => $option)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="options[{{ $i }}][name]" class="form-control" value="{{ $option['name'] }}" maxlength="50" placeholder="Option (e.g. Volume)" aria-label="Option name"></div>
                                    <div class="col"><input type="text" name="options[{{ $i }}][values]" class="form-control" value="{{ implode(', ', $option['values']) }}" placeholder="Values, comma separated" aria-label="Values"></div>
                                    <div class="col-auto"><button type="button" class="btn btn-icon btn-icon-danger remove-editable-row" title="Remove" aria-label="Remove"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-editable-row"
                            data-target="product-option-rows" data-prefix="options" data-first="name" data-second="values"
                            data-first-placeholder="Option (e.g. Volume)" data-second-placeholder="Values, comma separated">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add an option
                        </button>
                    </div>

                    <div class="ui-form-section">
                        <h3 class="ui-form-section-title">Specifications</h3>
                        <div id="product-spec-rows">
                            @foreach ($product->specs ?? [] as $key => $value)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="specs[{{ $loop->index }}][key]" class="form-control" value="{{ $key }}" maxlength="100" aria-label="Specification"></div>
                                    <div class="col"><input type="text" name="specs[{{ $loop->index }}][value]" class="form-control" value="{{ $value }}" aria-label="Value"></div>
                                    <div class="col-auto"><button type="button" class="btn btn-icon btn-icon-danger remove-editable-row" title="Remove" aria-label="Remove"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-editable-row"
                            data-target="product-spec-rows" data-prefix="specs" data-first="key" data-second="value"
                            data-first-placeholder="e.g. Origin" data-second-placeholder="e.g. China">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add a specification
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
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
                        node.setAttribute('aria-label', placeholder);
                        return node;
                    };
                    const col = (className, child) => { const div = document.createElement('div'); div.className = className; div.appendChild(child); return div; };
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-icon btn-icon-danger remove-editable-row';
                    remove.title = 'Remove';
                    remove.setAttribute('aria-label', 'Remove');
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-trash3';
                    remove.appendChild(icon);
                    const row = document.createElement('div');
                    row.className = 'row g-2 mb-2 editable-row';
                    row.append(
                        col('col-md-4', input(add.dataset.first, add.dataset.firstPlaceholder)),
                        col('col', input(add.dataset.second, add.dataset.secondPlaceholder)),
                        col('col-auto', remove)
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

    <script type="module">
        const PRODUCT_ID = {{ $product->id }};
        let stockChart = null;

        // Motif du mouvement et libellé de variante sont des saisies : on les échappe avant de les insérer dans le HTML
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        // Modification des informations générales (AJAX)
        $('#editProductForm').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: `/admin/products/main/${PRODUCT_ID}/update-details`,
                method: 'PATCH',
                data: $(this).serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        $('.bo-page-title').text(res.data.name);
                        $('#display-category').text(res.data.category_name);
                        $('#display-description').text($('#edit-product-description').val() || '—');
                        bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Product updated', showConfirmButton: false, timer: 2500 });
                    }
                },
            });
        });

        // Ouverture de la fenêtre de modification d'une variante : on reporte ses valeurs
        $('.edit-variant-btn').on('click', function () {
            const values = JSON.parse(this.dataset.values || '{}');

            document.querySelectorAll('#editVariantForm .variant-option-input').forEach(input => {
                input.value = values[input.dataset.optionName] ?? '';
            });
            document.querySelector('#editVariantForm .variant-brand-select').value = this.dataset.brandId || '';
            $('#edit-variant-stock').val(this.dataset.stock);
            $('#edit-variant-alert').val(this.dataset.alertStock);
            $('#edit-variant-price').val(this.dataset.price);
            $('#edit-variant-price-purchase').val(this.dataset.pricePurchase);
            $('#editVariantForm').attr('action', `/admin/products/variants/${this.dataset.id}`);
        });

        $('#variant-filter').on('change', function () {
            loadHistory($(this).val());
        });

        function loadHistory(variantId = '') {
            const $list = $('#history-list');
            $list.html('<li class="text-muted">Loading...</li>');

            $.getJSON(`/api/products/${PRODUCT_ID}/movements?variant_id=${variantId}`)
                .done(function (res) {
                    const html = res.data.map(m => {
                        const incoming = m.type === 'in';
                        return `
                            <li>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="ui-badge ui-badge-dot ${incoming ? 'ui-tone-success' : 'ui-tone-danger'}">${incoming ? 'In' : 'Out'}</span>
                                    <small class="text-muted">${new Date(m.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="mt-1">${escapeHtml(m.variant_label)} · <strong>${incoming ? '+' : '-'}${Math.abs(m.quantity)}</strong></div>
                                <div class="ui-cell-meta">${escapeHtml(m.reason)}</div>
                            </li>`;
                    }).join('');
                    $list.html(html || '<li class="ui-empty"><i class="bi bi-clock-history"></i>No movement found</li>');
                })
                .fail(function () {
                    $list.html('<li class="text-danger small">Loading error</li>');
                });
        }

        function renderChart(data) {
            const chartData = Array.isArray(data) ? data : Object.values(data).flat();
            const color = window.appTheme.token('chart-1');

            if (stockChart) {
                stockChart.data.labels = chartData.map(item => item.x);
                stockChart.data.datasets[0].data = chartData.map(item => item.y);
                stockChart.update();
                return;
            }

            stockChart = new Chart(document.getElementById('stockEvolutionChart'), {
                type: 'line',
                data: {
                    labels: chartData.map(item => item.x),
                    datasets: [{
                        label: 'Total stock',
                        data: chartData.map(item => item.y),
                        borderColor: color,
                        backgroundColor: window.appTheme.alpha(color, 0.08),
                        fill: true,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { type: 'time', time: { unit: 'day' }, grid: { display: false } },
                        y: { beginAtZero: true },
                    },
                },
            });
        }

        loadHistory();
        $.getJSON(`/api/products/${PRODUCT_ID}/stock-evolution`)
            .done(res => { if (res.success) renderChart(res.data); })
            .fail(err => console.error('Stock chart:', err));
    </script>
@endpush
