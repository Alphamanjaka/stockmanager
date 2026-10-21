@extends('layouts.app-back-office')

@section('title', __('New product'))

@section('content')
    <x-ui.errors />

    {{-- data-margin : le prix de vente de chaque variante suit son coût (resources/js/price-suggestion.js) --}}
    <form action="{{ route('admin.products.store') }}" method="POST" id="product-form"
        @if ($margin !== null) data-margin="{{ $margin }}" @endif>
        @csrf
        <div class="row g-3">
            <div class="col-xl-9">
                <div class="ui-stack">
                    {{-- 1. Informations du produit --}}
                    <x-ui.card :title="__('Product')">
                        <div class="row">
                            <x-ui.field class="col-md-6" name="name" :label="__('Product name')" required maxlength="255"
                                :placeholder="__('e.g. Desiccant gel, Straight wig')" :value="old('name')" />
                            <x-ui.field class="col-md-3" name="category_id" :label="__('Category')" required>
                                <select name="category_id" id="category_id" @class(['form-select', 'is-invalid' => $errors->has('category_id')]) required>
                                    <option value="">{{ __('Select…') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field class="col-md-3" name="price" :label="__('Default price')" type="number" step="0.01" min="0"
                                :hint="__('Used when a variant has no price.')" :value="old('price')" />
                            <x-ui.field class="col-12 mb-0" name="description" :label="__('Description')" type="textarea" rows="2"
                                :value="old('description')" />
                        </div>
                    </x-ui.card>

                    {{-- 2. Options : axes de variation --}}
                    <x-ui.card :title="__('Options')"
                        :subtitle="__('What makes the variants differ: e.g. Volume = 25 ml, 30 ml, or Color + Length. Leave empty for a product without variants.')">
                        <p class="small text-muted mb-3">{{ __('Choosing a category fills in its suggested options.') }}</p>
                        <div id="option-rows"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-option">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add an option') }}
                        </button>
                        <datalist id="option-names">
                            @foreach ($optionNames as $optionName)
                                <option value="{{ $optionName }}">
                            @endforeach
                        </datalist>
                    </x-ui.card>

                    {{-- 3. Grille des variantes générée à partir des options --}}
                    <x-ui.card :title="__('Variants')" flush
                        :subtitle="__('Uncheck the combinations you do not sell. SKUs are generated automatically. Stock starts at 0.')">
                        <x-slot:actions>
                            <span class="ui-tag">{!! __(':count variant(s)', ['count' => '<span id="variant-count">0</span>']) !!}</span>
                        </x-slot:actions>

                        <x-slot:toolbar>
                            {{-- Saisie groupée : appliquée aux lignes cochées --}}
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3"><label class="form-label small mb-1" for="bulk-cost">{{ __('Cost') }}</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="bulk-cost"></div>
                                <div class="col-6 col-md-3"><label class="form-label small mb-1" for="bulk-price">{{ __('Price') }}</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="bulk-price"></div>
                                <div class="col-6 col-md-2"><label class="form-label small mb-1" for="bulk-alert">{{ __('Alert') }}</label><input type="number" min="0" class="form-control form-control-sm" id="bulk-alert"></div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1" for="bulk-brand">{{ __('Brand') }}</label>
                                    <select class="form-select form-select-sm" id="bulk-brand">
                                        <option value="">—</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="bulk-apply">
                                        <i class="bi bi-check2-all" aria-hidden="true"></i> {{ __('Apply to checked') }}
                                    </button>
                                </div>
                            </div>
                        </x-slot:toolbar>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 40px"><span class="visually-hidden">{{ __('Sold') }}</span></th>
                                        <th>{{ __('Variant') }}</th>
                                        <th style="width: 160px">{{ __('Brand') }}</th>
                                        <th style="width: 120px">{{ __('Cost') }}</th>
                                        <th style="width: 120px">{{ __('Price') }}</th>
                                        <th style="width: 90px">{{ __('Alert') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="variant-rows"></tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2 border-top small text-muted">
                            @if ($margin !== null)
                                <div>
                                    <i class="bi bi-calculator" aria-hidden="true"></i>
                                    {{ __('Price = cost + :margin % (default margin, Settings › Stock & products).', ['margin' => rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.')]) }}
                                    {{ __('It stays editable: a price you type is kept.') }}
                                </div>
                            @endif
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            {{ __('The catalog does not manage stock: it increases when a purchase is received and decreases with each sale.') }}
                        </div>
                        <div class="px-3 pb-3 d-none" id="variant-warning">
                            <div class="alert alert-warning mb-0 mt-3"></div>
                        </div>
                    </x-ui.card>

                    {{-- 4. Spécifications libres --}}
                    <x-ui.card :title="__('Specifications')" :subtitle="__('Optional: origin, material, dimensions...')">
                        <div id="spec-rows"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-spec">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add a specification') }}
                        </button>
                    </x-ui.card>
                </div>
            </div>

            <div class="col-xl-3">
                <x-ui.card :title="__('Examples')">
                    <dl class="mb-0 small">
                        <dt>{{ __('Desiccant gel') }}</dt>
                        <dd class="text-muted">{!! __('Option <em>Volume</em>: 25 ml, 30 ml → 2 variants') !!}</dd>
                        <dt>{{ __('Wig') }}</dt>
                        <dd class="text-muted">{!! __('<em>Color</em>: Black, Brown · <em>Length</em>: 30 cm, 40 cm → 4 variants, brand per variant') !!}</dd>
                        <dt>{{ __('Simple product') }}</dt>
                        <dd class="text-muted mb-0">{{ __('No option → 1 variant') }}</dd>
                    </dl>
                </x-ui.card>
            </div>
        </div>

        <div class="ui-form-bar ui-form-actions">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save product') }}</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const MAX_VARIANTS = {{ \App\Modules\Catalog\Http\Requests\StoreProductRequest::MAX_VARIANTS }};
            const templates = @json($templates);
            const brands = @json($brands);
            const oldOptions = @json(old('options'));
            const oldSpecs = @json(old('specs'));
            let oldVariants = @json(old('variants'));
            const i18n = @js([
                'remove' => __('Remove'),
                'optionName' => __('Option (e.g. Volume)'),
                'optionValues' => __('Values, comma separated (e.g. 25 ml, 30 ml)'),
                'missingValues' => __('Add values for: :names.'),
                'tooMany' => __(':count combinations: the maximum is :max. Reduce the number of values.'),
                'defaultVariant' => __('Default variant (no option)'),
                'auto' => __('auto'),
                'specKey' => __('e.g. Origin'),
                'specValue' => __('e.g. China'),
                'replaceOptions' => __('Replace the current options with the ones suggested for this category?'),
            ]);

            const optionRows = document.getElementById('option-rows');
            const variantRows = document.getElementById('variant-rows');
            const specRows = document.getElementById('spec-rows');
            let optionIndex = 0;
            let specIndex = 0;

            // ---------- Petits outils DOM (aucune saisie n'est injectée en HTML) ----------
            function el(tag, attributes = {}, children = []) {
                const node = document.createElement(tag);
                Object.entries(attributes).forEach(([key, value]) => {
                    if (key === 'text') node.textContent = value;
                    else if (value !== null && value !== undefined) node.setAttribute(key, value);
                });
                children.forEach(child => node.appendChild(child));
                return node;
            }
            const cell = (child, className = '') => el('td', { class: className }, [child]);
            const removeButton = () => el('button', { type: 'button', class: 'btn btn-icon btn-icon-danger remove-row', title: i18n.remove, 'aria-label': i18n.remove }, [el('i', { class: 'bi bi-trash3', 'aria-hidden': 'true' })]);

            // ---------- Options ----------
            function addOptionRow(name = '', values = '') {
                const i = optionIndex++;
                const row = el('div', { class: 'row g-2 mb-2 option-row' }, [
                    el('div', { class: 'col-md-4' }, [el('input', { type: 'text', name: `options[${i}][name]`, class: 'form-control option-name', placeholder: i18n.optionName, list: 'option-names', maxlength: 50, value: name })]),
                    el('div', { class: 'col' }, [el('input', { type: 'text', name: `options[${i}][values]`, class: 'form-control option-values', placeholder: i18n.optionValues, value: values })]),
                    el('div', { class: 'col-auto' }, [removeButton()]),
                ]);
                optionRows.appendChild(row);
                return row;
            }

            // Même nettoyage que VariantOptions::normalizeOptions côté serveur
            function readOptions() {
                const options = [];
                const seen = [];
                optionRows.querySelectorAll('.option-row').forEach(row => {
                    const name = row.querySelector('.option-name').value.trim();
                    if (!name || seen.includes(name.toLowerCase())) return;
                    seen.push(name.toLowerCase());
                    const values = [];
                    row.querySelector('.option-values').value.split(',').map(v => v.trim()).forEach(value => {
                        if (value && !values.some(v => v.toLowerCase() === value.toLowerCase())) values.push(value);
                    });
                    options.push({ name, values });
                });
                return options;
            }

            function combinations(options) {
                return options.reduce((combos, option) => combos.flatMap(combo => option.values.map(value => ({ ...combo, [option.name]: value }))), [{}]);
            }
            const keyOf = values => JSON.stringify(Object.keys(values).sort().map(key => [key.toLowerCase(), String(values[key]).toLowerCase()]));

            // ---------- Grille des variantes ----------
            function snapshot() {
                const state = {};
                variantRows.querySelectorAll('tr').forEach(row => {
                    state[row.dataset.key] = {
                        enabled: row.querySelector('.v-enabled').checked,
                        price: row.querySelector('.v-price').value,
                        price_manual: row.querySelector('.v-price').dataset.priceManual || '',
                        price_purchase: row.querySelector('.v-cost').value,
                        alert_stock: row.querySelector('.v-alert').value,
                        brand_id: row.querySelector('.v-brand').value,
                    };
                });
                // Premier affichage après une erreur de validation : valeurs renvoyées par le serveur
                if (oldVariants) {
                    Object.values(oldVariants).forEach(variant => {
                        // Prix renvoyé par le serveur : gardé tel quel, même si le coût change ensuite
                        state[keyOf(variant.values || {})] = { ...variant, enabled: !!variant.enabled, price_manual: variant.price ? '1' : '' };
                    });
                    oldVariants = null;
                }
                return state;
            }

            function rebuildVariants() {
                const state = snapshot();
                const options = readOptions();
                const usable = options.filter(option => option.values.length > 0);
                const missing = options.filter(option => option.values.length === 0).map(option => option.name);
                const combos = combinations(usable);
                const warning = document.getElementById('variant-warning');

                variantRows.replaceChildren();
                const messages = [];
                if (missing.length) messages.push(i18n.missingValues.replace(':names', missing.join(', ')));
                if (combos.length > MAX_VARIANTS) messages.push(i18n.tooMany.replace(':count', combos.length).replace(':max', MAX_VARIANTS));
                warning.classList.toggle('d-none', messages.length === 0);
                warning.querySelector('.alert').textContent = messages.join(' ');
                if (combos.length > MAX_VARIANTS) { document.getElementById('variant-count').textContent = 0; return; }

                combos.forEach((values, i) => {
                    const previous = state[keyOf(values)] || {};
                    const label = Object.values(values).join(' / ') || i18n.defaultVariant;

                    const brandSelect = el('select', { name: `variants[${i}][brand_id]`, class: 'form-select form-select-sm v-brand' }, [el('option', { value: '', text: '—' })]);
                    brands.forEach(brand => brandSelect.appendChild(el('option', { value: brand.id, text: brand.name })));
                    brandSelect.value = previous.brand_id ?? '';

                    const enabled = el('input', { type: 'checkbox', name: `variants[${i}][enabled]`, value: 1, class: 'form-check-input v-enabled' });
                    enabled.checked = previous.enabled ?? true;

                    const labelCell = el('td', {}, [el('span', { class: 'fw-semibold', text: label })]);
                    Object.entries(values).forEach(([name, value]) => labelCell.appendChild(el('input', { type: 'hidden', name: `variants[${i}][values][${name}]`, value })));

                    const number = (field, className, extra = {}) => el('input', { type: 'number', min: 0, name: `variants[${i}][${field}]`, class: `form-control form-control-sm ${className}`, value: previous[field] ?? '', ...extra });
                    const price = number('price', 'v-price', { step: '0.01', placeholder: document.getElementById('price').value || '0', 'data-pricing-price': '' });
                    price.dataset.priceManual = previous.price_manual ?? '';
                    const row = el('tr', { 'data-key': keyOf(values), 'data-pricing': '' }, [
                        cell(enabled, 'text-center'),
                        labelCell,
                        cell(brandSelect),
                        cell(number('price_purchase', 'v-cost', { step: '0.01', placeholder: '0', 'data-pricing-cost': '' })),
                        cell(price),
                        cell(number('alert_stock', 'v-alert', { placeholder: i18n.auto })),
                    ]);
                    variantRows.appendChild(row);
                });
                document.getElementById('variant-count').textContent = combos.length;
            }

            // ---------- Spécifications ----------
            function addSpecRow(key = '', value = '') {
                const i = specIndex++;
                specRows.appendChild(el('div', { class: 'row g-2 mb-2 spec-row' }, [
                    el('div', { class: 'col-md-4' }, [el('input', { type: 'text', name: `specs[${i}][key]`, class: 'form-control', placeholder: i18n.specKey, maxlength: 100, value: key })]),
                    el('div', { class: 'col' }, [el('input', { type: 'text', name: `specs[${i}][value]`, class: 'form-control', placeholder: i18n.specValue, value })]),
                    el('div', { class: 'col-auto' }, [removeButton()]),
                ]));
            }

            // ---------- Événements ----------
            document.getElementById('add-option').addEventListener('click', () => { addOptionRow().querySelector('input').focus(); });
            document.getElementById('add-spec').addEventListener('click', () => addSpecRow());
            optionRows.addEventListener('input', rebuildVariants);
            document.addEventListener('click', event => {
                const button = event.target.closest('.remove-row');
                if (!button) return;
                button.closest('.option-row, .spec-row').remove();
                rebuildVariants();
            });

            document.getElementById('category_id').addEventListener('change', function () {
                const template = templates[this.value] || [];
                if (!template.length) return;
                const hasOptions = readOptions().length > 0;
                if (hasOptions && !confirm(i18n.replaceOptions)) return;
                optionRows.replaceChildren();
                template.forEach(option => addOptionRow(option.name, (option.values || []).join(', ')));
                rebuildVariants();
            });

            document.getElementById('bulk-apply').addEventListener('click', () => {
                const bulk = {
                    '.v-alert': document.getElementById('bulk-alert').value,
                    '.v-brand': document.getElementById('bulk-brand').value,
                };
                const cost = document.getElementById('bulk-cost').value;
                const price = document.getElementById('bulk-price').value;
                variantRows.querySelectorAll('tr').forEach(row => {
                    if (!row.querySelector('.v-enabled').checked) return;
                    Object.entries(bulk).forEach(([selector, value]) => { if (value !== '') row.querySelector(selector).value = value; });
                    // Le coût d'abord : il remplit le prix proposé (marge par défaut), sauf prix saisi à la main
                    if (cost !== '') {
                        const costInput = row.querySelector('.v-cost');
                        costInput.value = cost;
                        costInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (price !== '') {
                        const priceInput = row.querySelector('.v-price');
                        priceInput.value = price;
                        priceInput.dataset.priceManual = '1';
                    }
                });
            });

            // ---------- État initial ----------
            (oldOptions ? Object.values(oldOptions) : []).forEach(option => addOptionRow(option.name || '', option.values || ''));
            (oldSpecs ? Object.values(oldSpecs) : []).forEach(spec => addSpecRow(spec.key || '', spec.value || ''));
            rebuildVariants();
        })();
    </script>
@endpush
