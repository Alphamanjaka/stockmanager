@extends('layouts.app-back-office')
@section('title', 'Edit Purchase #' . $purchase->reference)

@section('content')
    <div class="row">
        <!-- Colonne de Gauche : Recherche et Saisie -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-search"></i> Add / Edit a Product
                </div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">Product</label>
                        <input type="text" id="product-search-input" class="form-control"
                            placeholder="Search by name or color..." autocomplete="off">
                        <input type="hidden" id="selected-product-id">
                        <div id="product-suggestions" class="list-group position-absolute w-100"
                            style="z-index: 1000; display: none;"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" id="input-quantity" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Purchase Price (U)</label>
                            <div class="input-group">
                                <input type="number" id="input-price" class="form-control" step="0.01">
                                <span class="input-group-text">Mga</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btn-add-item" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Update list
                    </button>
                </div>
            </div>
        </div>

        <!-- Colonne de Droite : Récapitulatif et Fournisseur -->
        <div class="col-md-7">
            <form action="{{ route('admin.purchases.update', $purchase->id) }}" method="POST" id="main-purchase-form">
                @csrf
                @method('PUT')

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-cart3"></i> Order Contents</span>
                        <span class="badge bg-light text-primary">Ref: {{ $purchase->reference }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody id="items-container">
                                {{-- Les lignes seront générées par JS au chargement --}}
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light p-4">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Supplier</label>
                                <select name="supplier_id" id="supplier-select" class="form-select" required>
                                    <option value="">-- Choose a supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($purchase->supplier_id == $supplier->id)>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="h4 mb-0">TOTAL NET</span>
                            <span class="h4 mb-0 text-primary fw-bold" id="total-display">0.00 Mga</span>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.purchases.show', $purchase->id) }}"
                                class="btn btn-outline-secondary flex-grow-1">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success flex-grow-2 px-5">
                                <i class="bi bi-check-all"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
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
            function renderTable() {
                itemsContainer.empty();
                let total = 0;

                currentItems.forEach((item, index) => {
                    const subtotal = item.quantity * item.unit_price;
                    total += subtotal;

                    itemsContainer.append(`
                        <tr>
                            <td>
                                <div class="fw-bold">${item.name}</div>
                                <input type="hidden" name="products[${index}][product_color_id]" value="${item.product_color_id}">
                                <input type="hidden" name="products[${index}][quantity]" value="${item.quantity}">
                                <input type="hidden" name="products[${index}][unit_price]" value="${item.unit_price}">
                            </td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${parseFloat(item.unit_price).toLocaleString('fr-FR', {minimumFractionDigits: 2})}</td>
                            <td class="text-end fw-bold">${subtotal.toLocaleString('fr-FR', {minimumFractionDigits: 2})} Mga</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item" data-index="${index}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${index}"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });

                totalDisplay.text(total.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2
                }) + ' Mga');
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
                            <button type="button" class="list-group-item list-group-item-action"
                                data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">
                                ${p.name} (Stock: ${p.stock})
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
                    alert('Please fill product, quantity and price fields correctly.');
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
