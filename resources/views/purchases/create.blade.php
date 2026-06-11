@extends('layouts.app-back-office')

@section('title', 'Purchase Cart Management')
@section('content')
    <div class="row">
        <!-- Colonne de Gauche : Recherche et Ajout -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-search"></i> Search Product
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.purchases.cart.add') }}" method="POST" id="add-to-cart-form">
                        @csrf
                        <div class="mb-3 position-relative"> {{-- Ajout de position-relative pour le positionnement des suggestions --}}
                            <label class="form-label fw-bold">Product</label>
                            <input type="text" id="product-search-input" class="form-control"
                                placeholder="Search a product by name or color..." autocomplete="off">
                            <input type="hidden" name="product_color_id" id="selected-product-id" required>
                            <div id="product-suggestions" class="list-group position-absolute w-100"
                                style="z-index: 1000; display: none;"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Prix d'Achat Unitaire</label>
                                <div class="input-group">
                                    <input type="number" name="unit_price" id="unit-price-input" class="form-control"
                                        step="0.01" required>
                                    <span class="input-group-text">Mga</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cart-plus"></i> Add to cart
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        <!-- Right column: The Cart -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-cart3"></i> Current Cart</span>
                    @if ($cartItems->isNotEmpty())
                        <form action="{{ route('admin.purchases.cart.clear') }}" method="POST"
                            onsubmit="return confirm('Clear the cart?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light text-danger">Vider</button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">P.U</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cartItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $item->product->product->name }}</div>
                                        <small class="text-muted">{{ $item->product->color->name }}</small>
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($item->subtotal, 2, ',', ' ') }} Mga</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.purchases.cart.remove', $item->product->id) }}"
                                            method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0"><i
                                                    class="bi bi-x-circle-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Your cart is empty</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($cartItems->isNotEmpty())
                    <div class="card-footer bg-light">
                        <form action="{{ route('admin.purchases.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fournisseur</label>
                                <select name="supplier_id" id="supplier-select" class="form-select" required>
                                    <option value="">-- Choisir un fournisseur --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 mb-0">TOTAL NET</span>
                                <span
                                    class="h4 mb-0 text-primary fw-bold">{{ number_format($cartItems->sum('subtotal'), 2, ',', ' ') }}
                                    Mga</span>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-all"></i> Valider la Commande
                            </button>
                        </form>
                    </div>
                @endif
            </div>
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
                            <button type="button" class="list-group-item list-group-item-action"
                                data-id="${product.id}"
                                data-price="${product.price}"
                                data-name="${product.name}">
                                ${product.name} (Stock: ${product.stock})
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
