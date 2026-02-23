@extends('layouts.app-front-office')

@section('title', 'New Sale')

@section('content')
    <form action="{{ route('sales.store') }}" method="POST" id="sale-form">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Products in Cart</div>
                    <div class="card-body">
                        <table class="table" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 120px;">Quantity</th>
                                    <th style="width: 150px;">Unit Price</th>
                                    <th style="width: 150px;">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr class="product-row">
                                    <td>
                                        <select name="products[0][product_id]" class="form-select product-select" required>
                                            <option value="" data-price="0" data-stock="0">Choose...</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                                    data-stock="{{ $product->quantity_stock }}">
                                                    {{ $product->name }} ({{ $product->quantity_stock }} available)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][quantity]" class="form-control qty-input"
                                            min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control price-display" readonly value="0.00 €">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control subtotal-display" readonly value="0.00 €">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i
                                                class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-primary btn-sm" id="add-product"><i class="bi bi-plus"></i>
                            Add Product</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white fw-bold">Transaction Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Gross :</span>
                            <span id="display-brut" class="fw-bold">0.00 Mga</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Discount (€)</label>
                            <input type="number" name="discount" id="discount-input" class="form-control" value="0"
                                min="0">
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total Net :</span>
                            <span id="display-net" class="h5 text-success fw-bold">0.00 Mga</span>
                        </div>

                        <div id="stock-warning" class="alert alert-warning d-none small">
                            <i class="bi bi-exclamation-triangle"></i> Attention : Some products exceed available stock!
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg shadow">
                            Validate Transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            const productList = $('#product-list');
            const discountInput = $('#discount-input');
            const addProductBtn = $('#add-product');
            const saleForm = $('#sale-form');
            const submitButton = saleForm.find('button[type="submit"]');
            const stockWarning = $('#stock-warning');

            // Configuration Select2 globale
            const select2Config = {
                theme: 'bootstrap-5',
                placeholder: 'Choose a product...',
                width: '100%'
            };

            function initSelect2(element) {
                element.select2(select2Config);
            }

            function updateFormState() {
                let totalBrut = 0;
                let hasStockError = false;
                let hasProducts = false;

                // On récupère tous les IDs sélectionnés pour la gestion des doublons
                const selectedIds = $('.product-select').map((_, el) => $(el).val()).get().filter(id => id !== "");

                $('.product-row').each(function() {
                    const row = $(this);
                    const select = row.find('.product-select');
                    const qtyInput = row.find('.qty-input');
                    const selectedOption = select.find(':selected');

                    // Gestion des options désactivées (doublons)
                    select.find('option').each(function() {
                        const option = $(this);
                        const val = option.val();
                        if (val && selectedIds.includes(val) && val !== select.val()) {
                            option.prop('disabled', true);
                        } else {
                            option.prop('disabled', false);
                        }
                    });

                    if (select.val()) {
                        hasProducts = true;
                        const price = parseFloat(selectedOption.data('price') || 0);
                        const stock = parseInt(selectedOption.data('stock') || 0);
                        const qty = parseInt(qtyInput.val() || 0);

                        const isStockError = qty > stock;
                        qtyInput.toggleClass('is-invalid', isStockError);
                        if (isStockError) hasStockError = true;

                        const subtotal = price * qty;
                        totalBrut += subtotal;

                        row.find('.price-display').val(price.toFixed(2) + ' €');
                        row.find('.subtotal-display').val(subtotal.toFixed(2) + ' €');
                    }
                });

                const discount = parseFloat(discountInput.val() || 0);
                const totalNet = Math.max(0, totalBrut - discount);

                $('#display-brut').text(totalBrut.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2
                }) + ' €');
                $('#display-net').text(totalNet.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2
                }) + ' €');

                stockWarning.toggleClass('d-none', !hasStockError);
                submitButton.prop('disabled', hasStockError || !hasProducts);
                updateFormState();
            }

            // Ajout ligne
            addProductBtn.on('click', function() {
                // 1. Récupérer tous les IDs déjà sélectionnés
                const selectedIds = $('.product-select').map((_, el) => $(el).val()).get().filter(id =>
                    id !== "");

                // 2. Vérifier s'il reste des produits à ajouter
                const totalAvailableOptions = $('.product-select').first().find('option').length -
                    1; // -1 pour l'option "Choose..."
                if (selectedIds.length >= totalAvailableOptions) {
                    alert("All available products are already in the cart.");
                    return;
                }

                const index = Date.now();
                const firstRow = $('.product-row').first();
                const newRow = firstRow.clone();

                // 3. Nettoyage complet du clone
                newRow.find('.select2-container').remove();
                const newSelect = newRow.find('select');
                newSelect.removeClass('select2-hidden-accessible').removeAttr('data-select2-id').empty();

                // 4. Reconstruction sélective des options
                // On reprend les options de la première ligne mais on exclut les IDs déjà pris
                firstRow.find('select option').each(function() {
                    const option = $(this).clone();
                    const val = option.val();

                    // On n'ajoute l'option que si elle n'est pas sélectionnée ailleurs (sauf l'option vide)
                    if (val === "" || !selectedIds.includes(val)) {
                        newSelect.append(option);
                    }
                });

                // 5. Mise à jour des index et réinitialisation des champs
                newRow.find('select, input').each(function() {
                    const el = $(this);
                    const name = el.attr('name');
                    if (name) el.attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                    el.val(el.is('input[type="number"]') ? 1 : '');
                    el.removeClass('is-invalid');
                });

                productList.append(newRow);
                initSelect2(newSelect);
                updateFormState();

                // Ouvrir automatiquement pour gagner du temps
                newSelect.select2('open');
            });

            // Suppression ligne
            productList.on('click', '.remove-row', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('tr').remove();
                    updateFormState();
                } else {
                    alert("At least one product is required.");
                }
            });

            // Events
            productList.on('change', '.product-select', updateFormState);
            productList.on('input', '.qty-input', updateFormState);
            discountInput.on('input', updateFormState);

            // Initialisation
            initSelect2($('.product-select'));

            // Si un produit change, on pourrait techniquement rafraîchir les autres SELECT,
            // mais le plus simple est de reconstruire les options au clic sur "Add Product"
            // comme nous venons de le faire ci-dessus.

            // Pour une sécurité maximale, on peut aussi désactiver les options dans les selects existants :
            function refreshAllSelects() {
                const selectedIds = $('.product-select').map((_, el) => $(el).val()).get();

                $('.product-select').each(function() {
                    const currentSelect = $(this);
                    const currentVal = currentSelect.val();

                    currentSelect.find('option').each(function() {
                        const option = $(this);
                        if (option.val() !== "" && selectedIds.includes(option.val()) && option
                            .val() !== currentVal) {
                            option.prop('disabled', true);
                        } else {
                            option.prop('disabled', false);
                        }
                    });
                    // On force Select2 à rafraîchir l'affichage visuel des options grisées
                    currentSelect.trigger('change.select2');
                });
            }

            // Appelle refreshAllSelects() à la fin de ton updateFormState()
        });
    </script>
@endpush
