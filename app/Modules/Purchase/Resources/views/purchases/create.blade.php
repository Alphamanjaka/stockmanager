@extends('layouts.app-back-office')

@section('title', 'New purchase')

@section('content')
    <div class="row g-3">
        {{-- Ajout d'un produit au panier --}}
        <div class="col-lg-5">
            <form action="{{ route('admin.purchases.cart.add') }}" method="POST" id="add-to-cart-form">
                @csrf
                <x-ui.card title="Add a product">
                    <div class="ui-field position-relative">
                        <label class="form-label required" for="product-search-input">Product</label>
                        <div class="ui-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="text" id="product-search-input" class="form-control"
                                placeholder="Search a product by name or variant... (F2)" autocomplete="off">
                        </div>
                        <input type="hidden" name="product_color_id" id="selected-product-id" required>
                        <div id="product-suggestions" class="list-group position-absolute w-100 shadow-lg"
                            style="z-index: 1000; display: none; max-height: 320px; overflow-y: auto;"></div>
                    </div>
                    <div class="row">
                        <x-ui.field class="col-md-5 mb-0" name="quantity" label="Quantity" type="number" min="1" value="1" required />
                        <x-ui.field class="col-md-7 mb-0" name="unit_price" id="unit-price-input" label="Unit purchase price" required>
                            <div class="input-group">
                                <input type="number" name="unit_price" id="unit-price-input" class="form-control" step="0.01" min="0" required>
                                <span class="input-group-text">{{ settings('currency_symbol', 'MGA') }}</span>
                            </div>
                        </x-ui.field>
                    </div>

                    <x-slot:footer>
                        <div class="ui-form-actions">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to cart
                            </button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
        </div>

        {{-- Panier --}}
        <div class="col-lg-7">
            <x-ui.card title="Cart" flush :count="$cartItems->count()">
                @if ($cartItems->isNotEmpty())
                    <x-slot:actions>
                        <form action="{{ route('admin.purchases.cart.clear') }}" method="POST"
                            data-confirm="Clear the cart?" data-confirm-tone="danger" data-confirm-ok="Clear">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-danger">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Clear
                            </button>
                        </form>
                    </x-slot:actions>
                @endif

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit price</th>
                                <th class="text-end">Total</th>
                                <th class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cartItems as $item)
                                <tr>
                                    <td>
                                        <div class="ui-cell-title">{{ $item->product->label }}</div>
                                        <div class="ui-cell-meta">{{ $item->product->sku }}</div>
                                    </td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->subtotal) }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.purchases.cart.remove', $item->product->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-icon-danger" title="Remove" aria-label="Remove">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <x-ui.empty-row :colspan="5" icon="bi-cart3">Your cart is empty. Search a product to add it.</x-ui.empty-row>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($cartItems->isNotEmpty())
                    <x-slot:footer>
                        <form action="{{ route('admin.purchases.store') }}" method="POST">
                            @csrf
                            <div class="row g-3 align-items-end py-2">
                                <x-ui.field class="col-md-6 mb-0" name="supplier_id" id="supplier-select" label="Supplier" required>
                                    <select name="supplier_id" id="supplier-select" class="form-select" required>
                                        <option value="">Choose a supplier...</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </x-ui.field>
                                <div class="col-md-6">
                                    <div class="ui-totals">
                                        <div class="ui-totals-grand mt-0 pt-0 border-0">
                                            <span>Net total</span><span>{{ money($cartItems->sum('subtotal')) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ui-form-actions pb-1">
                                <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> Create the purchase
                                </button>
                            </div>
                        </form>
                    </x-slot:footer>
                @endif
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            // Initialiser select2 pour le fournisseur seulement
            $('#supplier-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Prepare product data for client-side search
            const allProducts = @json($searchProducts);

            const productSearchInput = $('#product-search-input');
            const productSuggestions = $('#product-suggestions');
            const selectedProductIdInput = $('#selected-product-id');
            const unitPriceInput = $('#unit-price-input');

            // Les noms de produits/couleurs sont des saisies : on les échappe avant de les insérer dans le HTML
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            // Gérer la saisie dans le champ de recherche
            productSearchInput.on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                productSuggestions.empty();
                selectedProductIdInput.val(''); // Reset the selected product ID

                if (searchTerm.length < 2) { // Afficher les suggestions après 2 caractères
                    productSuggestions.hide();
                    return;
                }

                const filteredProducts = allProducts.filter(product =>
                    product.searchable.includes(searchTerm)
                );

                if (filteredProducts.length > 0) {
                    filteredProducts.forEach(product => {
                        const suggestionItem = $(`
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                data-id="${product.id}"
                                data-price="${product.price}"
                                data-name="${escapeHtml(product.name)}">
                                <span>${escapeHtml(product.name)}</span><span class="ui-tag">Stock: ${product.stock}</span>
                            </button>
                        `);
                        productSuggestions.append(suggestionItem);
                    });
                    productSuggestions.show();
                } else {
                    productSuggestions.hide();
                }
            });

            // Handle selection of a product in suggestions
            productSuggestions.on('click', '.list-group-item', function() {
                const selectedId = $(this).data('id');
                const selectedPrice = $(this).data('price');
                const selectedName = $(this).data('name');

                productSearchInput.val(selectedName);
                selectedProductIdInput.val(selectedId);
                unitPriceInput.val(selectedPrice).focus().select();
                productSuggestions.hide();
            });

            // Cacher les suggestions lorsque l'on clique en dehors
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#product-search-input, #product-suggestions').length) {
                    productSuggestions.hide();
                }
            });

            // Raccourci pour remettre le focus sur la recherche
            $(document).on('keydown', function(e) {
                if (e.key === "F2") {
                    e.preventDefault();
                    productSearchInput.focus();
                }
            });
        });
    </script>
@endpush
