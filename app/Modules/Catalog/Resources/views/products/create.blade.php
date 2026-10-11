@extends('layouts.app-back-office')

@section('title', 'Add New Product')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" id="product-form">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                {{-- 1. Informations du produit --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white"><i class="bi bi-box-seam me-2"></i> Product</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required maxlength="255" placeholder="e.g. Desiccant gel, Straight wig">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">Select…</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label">Default price (MGA)</label>
                                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" value="{{ old('price') }}"
                                    placeholder="Used when a variant has no price">
                            </div>
                        </div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- 2. Options : axes de variation --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white"><i class="bi bi-sliders me-2"></i> Options</div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            What makes the variants differ: e.g. <em>Volume</em> = 25 ml, 30 ml, or <em>Color</em> + <em>Length</em>.
                            Leave empty for a product without variants. Choosing a category fills in its suggested options.
                        </p>
                        <div id="option-rows"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-option">
                            <i class="bi bi-plus"></i> Add an option
                        </button>
                        <datalist id="option-names">
                            @foreach ($optionNames as $optionName)
                                <option value="{{ $optionName }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                {{-- 3. Grille des variantes générée à partir des options --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-grid-3x3-gap me-2"></i> Variants <span class="badge bg-light text-dark" id="variant-count">0</span></span>
                        <span class="small">Uncheck the combinations you do not sell. SKUs are generated automatically.</span>
                    </div>
                    <div class="card-body border-bottom bg-light py-2">
                        <div class="row g-2 align-items-end small">
                            <div class="col-md-2"><label class="form-label mb-0">Price</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="bulk-price"></div>
                            <div class="col-md-2"><label class="form-label mb-0">Cost</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="bulk-cost"></div>
                            <div class="col-md-2"><label class="form-label mb-0">Stock</label><input type="number" min="0" class="form-control form-control-sm" id="bulk-stock"></div>
                            <div class="col-md-2"><label class="form-label mb-0">Alert</label><input type="number" min="0" class="form-control form-control-sm" id="bulk-alert"></div>
                            <div class="col-md-2">
                                <label class="form-label mb-0">Brand</label>
                                <select class="form-select form-select-sm" id="bulk-brand">
                                    <option value="">—</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><button type="button" class="btn btn-sm btn-secondary w-100" id="bulk-apply">Apply to checked</button></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px"></th>
                                    <th>Variant</th>
                                    <th style="width: 150px">Brand</th>
                                    <th style="width: 120px">Price</th>
                                    <th style="width: 120px">Cost</th>
                                    <th style="width: 100px">Initial stock</th>
                                    <th style="width: 90px">Alert</th>
                                </tr>
                            </thead>
                            <tbody id="variant-rows"></tbody>
                        </table>
                    </div>
                    <div class="card-body py-2 d-none" id="variant-warning"><div class="alert alert-warning mb-0 small"></div></div>
                </div>

                {{-- 4. Spécifications libres --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Specifications <span class="text-muted fw-normal small">(optional)</span></div>
                    <div class="card-body">
                        <div id="spec-rows"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-spec"><i class="bi bi-plus"></i> Add a specification</button>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5"><i class="bi bi-save me-2"></i> Save Product</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white"><i class="bi bi-lightbulb me-2"></i> Examples</div>
                    <div class="card-body small">
                        <p class="mb-2"><strong>Desiccant gel</strong><br>Option <em>Volume</em>: 25 ml, 30 ml<br>→ 2 variants</p>
                        <p class="mb-2"><strong>Wig</strong><br><em>Color</em>: Black, Brown<br><em>Length</em>: 30 cm, 40 cm<br>→ 4 variants, brand per variant</p>
                        <p class="mb-0"><strong>Simple product</strong><br>No option → 1 variant</p>
                    </div>
                </div>
            </div>
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
            const removeButton = () => el('button', { type: 'button', class: 'btn btn-outline-danger w-100 remove-row', title: 'Remove' }, [el('i', { class: 'bi bi-trash' })]);

            // ---------- Options ----------
            function addOptionRow(name = '', values = '') {
                const i = optionIndex++;
                const row = el('div', { class: 'row g-2 mb-2 option-row' }, [
                    el('div', { class: 'col-md-4' }, [el('input', { type: 'text', name: `options[${i}][name]`, class: 'form-control option-name', placeholder: 'Option (e.g. Volume)', list: 'option-names', maxlength: 50, value: name })]),
                    el('div', { class: 'col-md-7' }, [el('input', { type: 'text', name: `options[${i}][values]`, class: 'form-control option-values', placeholder: 'Values, comma separated (e.g. 25 ml, 30 ml)', value: values })]),
                    el('div', { class: 'col-md-1' }, [removeButton()]),
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
                        price_purchase: row.querySelector('.v-cost').value,
                        stock: row.querySelector('.v-stock').value,
                        alert_stock: row.querySelector('.v-alert').value,
                        brand_id: row.querySelector('.v-brand').value,
                    };
                });
                // Premier affichage après une erreur de validation : valeurs renvoyées par le serveur
                if (oldVariants) {
                    Object.values(oldVariants).forEach(variant => {
                        state[keyOf(variant.values || {})] = { ...variant, enabled: !!variant.enabled };
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
                if (missing.length) messages.push(`Add values for: ${missing.join(', ')}.`);
                if (combos.length > MAX_VARIANTS) messages.push(`${combos.length} combinations: the maximum is ${MAX_VARIANTS}. Reduce the number of values.`);
                warning.classList.toggle('d-none', messages.length === 0);
                warning.querySelector('.alert').textContent = messages.join(' ');
                if (combos.length > MAX_VARIANTS) { document.getElementById('variant-count').textContent = 0; return; }

                combos.forEach((values, i) => {
                    const previous = state[keyOf(values)] || {};
                    const label = Object.values(values).join(' / ') || 'Default variant (no option)';

                    const brandSelect = el('select', { name: `variants[${i}][brand_id]`, class: 'form-select form-select-sm v-brand' }, [el('option', { value: '', text: '—' })]);
                    brands.forEach(brand => brandSelect.appendChild(el('option', { value: brand.id, text: brand.name })));
                    brandSelect.value = previous.brand_id ?? '';

                    const enabled = el('input', { type: 'checkbox', name: `variants[${i}][enabled]`, value: 1, class: 'form-check-input v-enabled' });
                    enabled.checked = previous.enabled ?? true;

                    const labelCell = el('td', {}, [el('span', { class: 'fw-semibold', text: label })]);
                    Object.entries(values).forEach(([name, value]) => labelCell.appendChild(el('input', { type: 'hidden', name: `variants[${i}][values][${name}]`, value })));

                    const number = (field, className, extra = {}) => el('input', { type: 'number', min: 0, name: `variants[${i}][${field}]`, class: `form-control form-control-sm ${className}`, value: previous[field] ?? '', ...extra });
                    const row = el('tr', { 'data-key': keyOf(values) }, [
                        cell(enabled, 'text-center'),
                        labelCell,
                        cell(brandSelect),
                        cell(number('price', 'v-price', { step: '0.01', placeholder: document.getElementById('price').value || '0' })),
                        cell(number('price_purchase', 'v-cost', { step: '0.01', placeholder: '0' })),
                        cell(number('stock', 'v-stock', { placeholder: '0' })),
                        cell(number('alert_stock', 'v-alert', { placeholder: 'auto' })),
                    ]);
                    variantRows.appendChild(row);
                });
                document.getElementById('variant-count').textContent = combos.length;
            }

            // ---------- Spécifications ----------
            function addSpecRow(key = '', value = '') {
                const i = specIndex++;
                specRows.appendChild(el('div', { class: 'row g-2 mb-2 spec-row' }, [
                    el('div', { class: 'col-md-4' }, [el('input', { type: 'text', name: `specs[${i}][key]`, class: 'form-control', placeholder: 'e.g. Origin', maxlength: 100, value: key })]),
                    el('div', { class: 'col-md-7' }, [el('input', { type: 'text', name: `specs[${i}][value]`, class: 'form-control', placeholder: 'e.g. China', value })]),
                    el('div', { class: 'col-md-1' }, [removeButton()]),
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
                if (hasOptions && !confirm('Replace the current options with the ones suggested for this category?')) return;
                optionRows.replaceChildren();
                template.forEach(option => addOptionRow(option.name, (option.values || []).join(', ')));
                rebuildVariants();
            });

            document.getElementById('bulk-apply').addEventListener('click', () => {
                const bulk = {
                    '.v-price': document.getElementById('bulk-price').value,
                    '.v-cost': document.getElementById('bulk-cost').value,
                    '.v-stock': document.getElementById('bulk-stock').value,
                    '.v-alert': document.getElementById('bulk-alert').value,
                    '.v-brand': document.getElementById('bulk-brand').value,
                };
                variantRows.querySelectorAll('tr').forEach(row => {
                    if (!row.querySelector('.v-enabled').checked) return;
                    Object.entries(bulk).forEach(([selector, value]) => { if (value !== '') row.querySelector(selector).value = value; });
                });
            });

            // ---------- État initial ----------
            (oldOptions ? Object.values(oldOptions) : []).forEach(option => addOptionRow(option.name || '', option.values || ''));
            (oldSpecs ? Object.values(oldSpecs) : []).forEach(spec => addSpecRow(spec.key || '', spec.value || ''));
            rebuildVariants();
        })();
    </script>
@endpush
