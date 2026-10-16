@extends('layouts.app-back-office')

@section('title', 'Edit purchase')
@section('subtitle', $purchase->reference)

@section('content')
    <div class="row g-3">
        {{-- Saisie d'une ligne (ajout ou modification) --}}
        <div class="col-lg-5">
            <div class="sticky-top" style="top: calc(var(--bo-sticky-offset, 0px) + 1rem); z-index: 1;">
                <x-ui.card title="Add or update a line">
                    <div class="ui-field position-relative">
                        <label class="form-label" for="product-search-input">Product</label>
                        <div class="ui-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="text" id="product-search-input" class="form-control"
                                placeholder="Search by name or variant..." autocomplete="off">
                        </div>
                        <input type="hidden" id="selected-product-id">
                        <div id="product-suggestions" class="list-group position-absolute w-100 shadow-lg"
                            style="z-index: 1000; display: none; max-height: 320px; overflow-y: auto;"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-5 ui-field mb-0">
                            <label class="form-label" for="input-quantity">Quantity</label>
                            <input type="number" id="input-quantity" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-7 ui-field mb-0">
                            <label class="form-label" for="input-price">Unit purchase price</label>
                            <div class="input-group">
                                <input type="number" id="input-price" class="form-control" step="0.01" min="0">
                                <span class="input-group-text">{{ settings('currency_symbol', 'MGA') }}</span>
                            </div>
                        </div>
                    </div>

                    <x-slot:footer>
                        <div class="ui-form-actions">
                            <button type="button" id="btn-add-item" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-down-circle" aria-hidden="true"></i> Update the list
                            </button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </div>
        </div>

        {{-- Contenu de l'achat et fournisseur --}}
        <div class="col-lg-7">
            <form action="{{ route('admin.purchases.update', $purchase->id) }}" method="POST" id="main-purchase-form">
                @csrf
                @method('PUT')

                <x-ui.card title="Purchase lines" flush>
                    <x-slot:actions>
                        <x-ui.status :value="$purchase->state" />
                    </x-slot:actions>

                    <div class="table-responsive">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit price</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody id="items-container">
                                {{-- Lignes générées en JavaScript au chargement --}}
                            </tbody>
                        </table>
                    </div>

                    <x-slot:footer>
                        <div class="row g-3 align-items-end py-2">
                            <x-ui.field class="col-md-6 mb-0" name="supplier_id" id="supplier-select" label="Supplier" required>
                                <select name="supplier_id" id="supplier-select" class="form-select" required>
                                    <option value="">Choose a supplier...</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($purchase->supplier_id == $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <div class="col-md-6">
                                <div class="ui-totals">
                                    <div class="ui-totals-grand mt-0 pt-0 border-0">
                                        <span>Net total</span><span id="total-display">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ui-form-actions pb-1">
                            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Save changes
                            </button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            // Données initiales (Provenant du backend)
            const allProducts = @json($searchProducts);
            let currentItems = @json($currentItems);

            // Sélecteurs
            const productSearchInput = $('#product-search-input');
            const productSuggestions = $('#product-suggestions');
            const selectedIdInput = $('#selected-product-id');
            const inputQty = $('#input-quantity');
            const inputPrice = $('#input-price');
            const itemsContainer = $('#items-container');
            const totalDisplay = $('#total-display');

            $('#supplier-select').select2({
                theme: 'bootstrap-5'
            });

            // Rendu de la table
            // Les noms de produits/couleurs sont des saisies : on les échappe avant de les insérer dans le HTML
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            // Même format que money() côté serveur (devise et position des paramètres)
            const currency = {{ Js::from(settings('currency_symbol', 'MGA')) }};
            const currencyBefore = {{ Js::from(settings('currency_position', 'after') === 'before') }};
            const formatMoney = (amount) => {
                const value = Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return currencyBefore ? `${currency} ${value}` : `${value} ${currency}`;
            };

            function renderTable() {
                itemsContainer.empty();
                let total = 0;

                currentItems.forEach((item, index) => {
                    const subtotal = item.quantity * item.unit_price;
                    total += subtotal;

                    itemsContainer.append(`
                        <tr>
                            <td>
                                <div class="ui-cell-title">${escapeHtml(item.name)}</div>
                                <input type="hidden" name="products[${index}][product_color_id]" value="${item.product_color_id}">
                                <input type="hidden" name="products[${index}][quantity]" value="${item.quantity}">
                                <input type="hidden" name="products[${index}][unit_price]" value="${item.unit_price}">
                            </td>
                            <td class="text-end">${item.quantity}</td>
                            <td class="text-end">${formatMoney(item.unit_price)}</td>
                            <td class="text-end fw-semibold">${formatMoney(subtotal)}</td>
                            <td class="text-end">
                                <div class="ui-row-actions">
                                    <button type="button" class="btn btn-icon btn-edit-item" data-index="${index}" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-icon btn-icon-danger btn-remove-item" data-index="${index}" title="Remove" aria-label="Remove"><i class="bi bi-trash3"></i></button>
                                </div>
                            </td>
                        </tr>
                    `);
                });

                if (currentItems.length === 0) {
                    itemsContainer.append('<tr class="ui-empty-row"><td colspan="5"><div class="ui-empty"><i class="bi bi-cart3"></i><div>No line yet. Add a product on the left.</div></div></td></tr>');
                }

                totalDisplay.text(formatMoney(total));
            }

            // Gestion de la recherche (Autocomplete)
            productSearchInput.on('input', function() {
                const term = $(this).val().toLowerCase();
                productSuggestions.empty().hide();
                if (term.length < 2) return;

                const filtered = allProducts.filter(p => p.searchable.includes(term));
                if (filtered.length > 0) {
                    filtered.forEach(p => {
                        productSuggestions.append(`
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-price="${p.price}">
                                <span>${escapeHtml(p.name)}</span><span class="ui-tag">Stock: ${p.stock}</span>
                            </button>
                        `);
                    });
                    productSuggestions.show();
                }
            });

            // Product selection
            productSuggestions.on('click', '.list-group-item', function() {
                const p = $(this).data();
                productSearchInput.val(p.name);
                selectedIdInput.val(p.id);
                inputPrice.val(p.price);
                productSuggestions.hide();
                inputQty.focus();
            });

            // Ajouter/Mettre à jour un item
            $('#btn-add-item').on('click', function() {
                const id = selectedIdInput.val();
                const name = productSearchInput.val();
                const qty = parseInt(inputQty.val());
                const price = parseFloat(inputPrice.val());

                if (!id || qty <= 0 || isNaN(price)) {
                    Swal.fire({ icon: 'warning', text: 'Please fill product, quantity and price fields correctly.' });
                    return;
                }

                const existingIndex = currentItems.findIndex(i => i.product_color_id == id);
                if (existingIndex > -1) {
                    currentItems[existingIndex].quantity = qty;
                    currentItems[existingIndex].unit_price = price;
                } else {
                    currentItems.push({
                        product_color_id: id,
                        name,
                        quantity: qty,
                        unit_price: price
                    });
                }

                // Reset form
                productSearchInput.val('');
                selectedIdInput.val('');
                inputQty.val(1);
                inputPrice.val('');

                renderTable();
            });

            // Supprimer
            itemsContainer.on('click', '.btn-remove-item', function() {
                const index = $(this).data('index');
                currentItems.splice(index, 1);
                renderTable();
            });

            // Editer (Remonter dans le formulaire)
            itemsContainer.on('click', '.btn-edit-item', function() {
                const index = $(this).data('index');
                const item = currentItems[index];

                productSearchInput.val(item.name);
                selectedIdInput.val(item.product_color_id);
                inputQty.val(item.quantity);
                inputPrice.val(item.unit_price);

                currentItems.splice(index, 1);
                renderTable();
            });

            // Initialisation
            renderTable();
        });
    </script>
@endpush
