@extends('layouts.app-back-office')

@section('title', $product->name)

@section('subtitle')
    <span id="display-category">{{ $product->category->name ?? __('N/A') }}</span>
    · {!! __(':count variant(s)', ['count' => '<span id="variant-count">' . $variants->count() . '</span>']) !!}
@endsection

@section('actions')
    <x-ui.actions-menu>
        <x-ui.menu-item href="#" data-bs-toggle="modal" data-bs-target="#optionsModal" icon="bi-sliders">
            {{ __('Options & specifications') }}
        </x-ui.menu-item>
        <x-ui.menu-item :href="route('admin.labels.create', ['product' => $product->id])" icon="bi-upc-scan">
            {{ __('Print labels') }}
        </x-ui.menu-item>
        @unless ($product->isArchived())
            <li><hr class="dropdown-divider"></li>
            <x-ui.menu-item :action="route('admin.products.archive')" :ids="[$product->id]" icon="bi-archive"
                :confirm="__('Archive this product and all its variants?')"
                :confirm-text="__('It will no longer be offered at the checkout or in purchase orders. Its history is kept.')">
                {{ __('Archive') }}
            </x-ui.menu-item>
        @endunless
    </x-ui.actions-menu>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProductModal">
        <i class="bi bi-pencil" aria-hidden="true"></i> {{ __('Edit') }}
    </button>
@endsection

@php $canAddVariant = !empty($product->options) || $variants->isEmpty(); @endphp

@section('content')
    @if ($product->isArchived())
        <x-ui.archived-notice :restore="route('admin.products.restore')" :id="$product->id" :date="$product->archived_at">
            {!! __('This product is <strong>archived</strong>: it is no longer offered at the checkout or in purchase orders.') !!}
        </x-ui.archived-notice>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="ui-stack">
                <x-ui.card :title="__('Details')">
                    <dl class="ui-dl">
                        <dt>{{ __('Options') }}</dt>
                        <dd class="d-flex flex-wrap gap-1">
                            @forelse ($product->options ?? [] as $option)
                                <span class="ui-tag">{{ $option['name'] }} : {{ implode(', ', $option['values']) }}</span>
                            @empty
                                <span class="text-muted">{{ __('No options (single variant)') }}</span>
                            @endforelse
                        </dd>
                        @foreach ($product->specs ?? [] as $key => $value)
                            <dt>{{ $key }}</dt>
                            <dd>{{ $value }}</dd>
                        @endforeach
                        <dt>{{ __('Description') }}</dt>
                        <dd class="text-prewrap" id="display-description">{{ $product->description ?: '—' }}</dd>
                    </dl>
                </x-ui.card>

                <x-ui.card :title="__('Variants & stock')" flush
                    :subtitle="__('Stock is read-only here: it changes with received purchases, sales, stock counts and corrections.')">
                    <x-slot:actions>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addVariantModal"
                            @disabled(!$canAddVariant) title="{{ $canAddVariant ? '' : __('Add an option first (options & specifications)') }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add a variant') }}
                        </button>
                    </x-slot:actions>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Variant') }}</th>
                                    <th class="text-end">{{ __('Purchase price') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Margin') }}</th>
                                    <th class="text-end">{{ __('Stock') }}</th>
                                    <th class="text-end">{{ __('Alert at') }}</th>
                                    <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody id="variants-table-body">
                                @forelse ($variants as $variant)
                                    <tr>
                                        <td>
                                            <div class="ui-cell-title">{{ $variant->variant_name ?: __('Default') }}</div>
                                            <div class="ui-cell-meta">{{ $variant->sku }}</div>
                                            @if ($variant->barcode)
                                                <div class="ui-cell-meta"><i class="bi bi-upc" aria-hidden="true"></i> {{ $variant->barcode }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ money($variant->price_purchase) }}</td>
                                        <td class="text-end fw-semibold">{{ money($variant->price) }}</td>
                                        <td class="text-end text-nowrap">
                                            @if ($variant->unitMargin() === null)
                                                <span class="text-muted" title="{{ __('No purchase price') }}">—</span>
                                            @else
                                                <span @class(['text-danger' => $variant->unitMargin() < 0])>{{ money($variant->unitMargin()) }}</span>
                                                <div class="ui-cell-meta">{{ __(':rate % of the price', ['rate' => $variant->marginRate() ?? 0]) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <x-ui.badge :tone="$variant->stock <= $variant->alert_stock ? 'danger' : 'success'" dot>{{ $variant->stock }}</x-ui.badge>
                                        </td>
                                        <td class="text-end text-muted">{{ $variant->alert_stock }}</td>
                                        <td class="text-end">
                                            <div class="ui-row-actions">
                                                <button type="button" class="btn btn-icon edit-variant-btn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"
                                                    data-bs-toggle="modal" data-bs-target="#editVariantModal"
                                                    data-id="{{ $variant->id }}"
                                                    data-values="{{ json_encode($variant->option_values ?? new stdClass()) }}"
                                                    data-brand-id="{{ $variant->brand_id }}"
                                                    data-stock="{{ $variant->stock }}"
                                                    data-alert-stock="{{ $variant->alert_stock }}"
                                                    data-price="{{ $variant->price }}"
                                                    data-price-purchase="{{ $variant->price_purchase }}"
                                                    data-barcode="{{ $variant->barcode }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                {{-- Secours en cas d'erreur de saisie : correction par un mouvement de stock --}}
                                                @if (Route::has('admin.movements.create'))
                                                    <a href="{{ route('admin.movements.create', ['variant' => $variant->id]) }}" class="btn btn-icon"
                                                        title="{{ __('Stock correction') }}" aria-label="{{ __('Stock correction') }}">
                                                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                <x-ui.delete-button :action="route('admin.products.variants.destroy', $variant->id)"
                                                    :confirm="__('Delete this variant?')" />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-ui.empty-row :colspan="7" icon="bi-box">{{ __('No variant for this product.') }}</x-ui.empty-row>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card :title="__('Stock evolution')" :subtitle="__('Total stock of all variants')">
                    <div style="height: 260px;">
                        <canvas id="stockEvolutionChart" role="img" aria-label="{{ __('Stock evolution') }}"></canvas>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="col-xl-4">
            <x-ui.card :title="__('Movement history')" flush>
                <x-slot:toolbar>
                    <select id="variant-filter" class="form-select form-select-sm" aria-label="{{ __('Variant') }}">
                        <option value="">{{ __('All variants') }}</option>
                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->variant_name ?: __('Default') }} ({{ $variant->sku }})</option>
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
                    <h2 class="modal-title" id="editProductModalTitle">{{ __('Edit product') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <x-ui.field name="name" id="edit-product-name" :label="__('Product name')" required :value="$product->name" />
                    <x-ui.field name="category_id" :label="__('Category')">
                        <select name="category_id" id="category_id" class="form-select">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($product->category_id == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field class="mb-0" name="description" id="edit-product-description" :label="__('Description')" type="textarea"
                        rows="3" :value="$product->description" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ajouter une variante --}}
    <div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            {{-- data-margin : le prix de vente suit le prix d'achat (resources/js/price-suggestion.js) --}}
            @php $margin = \App\Modules\Catalog\Support\Pricing::defaultMargin(); @endphp
            <form action="{{ route('admin.products.variants.store', $product->id) }}" method="POST" class="modal-content" data-pricing
                @if ($margin !== null) data-margin="{{ $margin }}" @endif>
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title" id="addVariantModalTitle">{{ __('New variant') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @include('catalog::products._variant-identity', ['prefix' => 'add'])
                        @include('catalog::products._variant-figures', ['prefix' => 'add', 'variant' => null])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save variant') }}</button>
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
                    <h2 class="modal-title" id="editVariantModalTitle">{{ __('Edit variant') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @include('catalog::products._variant-identity', ['prefix' => 'edit'])
                        @include('catalog::products._variant-figures', ['prefix' => 'edit'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Apply changes') }}</button>
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
                    <h2 class="modal-title" id="optionsModalTitle">{{ __('Options & specifications') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="ui-form-section">
                        <h3 class="ui-form-section-title">{{ __('Options') }}</h3>
                        <p class="ui-form-section-hint">
                            {{ __('Values used by a variant cannot be removed. New values can be added here or directly when adding a variant.') }}
                        </p>
                        <div id="product-option-rows">
                            @foreach ($product->options ?? [] as $i => $option)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="options[{{ $i }}][name]" class="form-control" value="{{ $option['name'] }}" maxlength="50" placeholder="{{ __('Option (e.g. Volume)') }}" aria-label="{{ __('Option name') }}"></div>
                                    <div class="col"><input type="text" name="options[{{ $i }}][values]" class="form-control" value="{{ implode(', ', $option['values']) }}" placeholder="{{ __('Values, comma separated') }}" aria-label="{{ __('Values') }}"></div>
                                    <div class="col-auto"><button type="button" class="btn btn-icon btn-icon-danger remove-editable-row" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-editable-row"
                            data-target="product-option-rows" data-prefix="options" data-first="name" data-second="values"
                            data-first-placeholder="{{ __('Option (e.g. Volume)') }}" data-second-placeholder="{{ __('Values, comma separated') }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add an option') }}
                        </button>
                    </div>

                    <div class="ui-form-section">
                        <h3 class="ui-form-section-title">{{ __('Specifications') }}</h3>
                        <div id="product-spec-rows">
                            @foreach ($product->specs ?? [] as $key => $value)
                                <div class="row g-2 mb-2 editable-row">
                                    <div class="col-md-4"><input type="text" name="specs[{{ $loop->index }}][key]" class="form-control" value="{{ $key }}" maxlength="100" aria-label="{{ __('Specification') }}"></div>
                                    <div class="col"><input type="text" name="specs[{{ $loop->index }}][value]" class="form-control" value="{{ $value }}" aria-label="{{ __('Value') }}"></div>
                                    <div class="col-auto"><button type="button" class="btn btn-icon btn-icon-danger remove-editable-row" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-editable-row"
                            data-target="product-spec-rows" data-prefix="specs" data-first="key" data-second="value"
                            data-first-placeholder="{{ __('e.g. Origin') }}" data-second-placeholder="{{ __('e.g. China') }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add a specification') }}
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Lignes ajoutables / supprimables (options et spécifications) : construites avec le DOM, sans HTML injecté
        (function () {
            const removeLabel = @js(__('Remove'));
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
                    remove.title = removeLabel;
                    remove.setAttribute('aria-label', removeLabel);
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
        const i18n = @js([
            'updated' => __('Product updated'),
            'loading' => __('Loading...'),
            'in' => __('In'),
            'out' => __('Out'),
            'noMovement' => __('No movement found'),
            'loadingError' => __('Loading error'),
            'totalStock' => __('Total stock'),
        ]);
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
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: i18n.updated, showConfirmButton: false, timer: 2500 });
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
            $('#edit-variant-barcode').val(this.dataset.barcode);
            $('#editVariantForm').attr('action', `/admin/products/variants/${this.dataset.id}`);
        });

        $('#variant-filter').on('change', function () {
            loadHistory($(this).val());
        });

        function loadHistory(variantId = '') {
            const $list = $('#history-list');
            $list.html(`<li class="text-muted">${escapeHtml(i18n.loading)}</li>`);

            $.getJSON(`/api/products/${PRODUCT_ID}/movements?variant_id=${variantId}`)
                .done(function (res) {
                    const html = res.data.map(m => {
                        const incoming = m.type === 'in';
                        return `
                            <li>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="ui-badge ui-badge-dot ${incoming ? 'ui-tone-success' : 'ui-tone-danger'}">${escapeHtml(incoming ? i18n.in : i18n.out)}</span>
                                    <small class="text-muted">${new Date(m.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="mt-1">${escapeHtml(m.variant_label)} · <strong>${incoming ? '+' : '-'}${Math.abs(m.quantity)}</strong></div>
                                <div class="ui-cell-meta">${escapeHtml(m.reason)}</div>
                            </li>`;
                    }).join('');
                    $list.html(html || `<li class="ui-empty"><i class="bi bi-clock-history"></i>${escapeHtml(i18n.noMovement)}</li>`);
                })
                .fail(function () {
                    $list.html(`<li class="text-danger small">${escapeHtml(i18n.loadingError)}</li>`);
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
                        label: i18n.totalStock,
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
