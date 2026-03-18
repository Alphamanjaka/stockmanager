@extends('layouts.app-front-office')

@section('title', 'New Sale')

@section('content')
    <form action="{{ route('saler.store') }}" method="POST" id="sale-form">
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
            const saleForm = $('#sale-form');

            // On stocke le HTML des options UNE SEULE FOIS au chargement
            const originalOptionsHtml = $('.product-select').first().html();

            // Initialisation légère
            function initS2(el) {
                el.select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            // --- CALCULS OPTIMISÉS ---
            function fastUpdate() {
                let totalBrut = 0;
                const selectedIds = [];

                // Une seule boucle pour tout faire
                $('.product-row').each(function() {
                    const row = $(this);
                    const select = row.find('.product-select');
                    const val = select.val();

                    if (val) {
                        selectedIds.push(val);
                        const opt = select.find(':selected');
                        const qty = parseInt(row.find('.qty-input').val() || 0);
                        const price = parseFloat(opt.data('price') || 0);
                        const stock = parseInt(opt.data('stock') || 0);
                        const remaining = stock - qty;
                        row.find('.stock-feedback').text(`${remaining} restants`).toggleClass('text-danger',
                            remaining < 5);

                        // Calcul direct
                        const subtotal = price * qty;
                        totalBrut += subtotal;

                        // Update UI minimaliste (uniquement si nécessaire)
                        row.find('.subtotal-display').val(subtotal.toFixed(2) + ' €');
                        row.find('.qty-input').toggleClass('is-invalid', qty > stock);
                    }
                });

                const net = Math.max(0, totalBrut - parseFloat($('#discount-input').val() || 0));
                $('#display-brut').text(totalBrut.toFixed(2) + ' €');
                $('#display-net').text(net.toFixed(2) + ' €');

                // On désactive le bouton si stock error
                saleForm.find('button[type="submit"]').prop('disabled', $('.is-invalid').length > 0 || selectedIds
                    .length === 0);
                return selectedIds;
            }

            // --- AJOUT DE LIGNE ULTRA-RAPIDE ---
            $('#add-product').on('click', function() {
                const selectedIds = fastUpdate();
                const index = Date.now();

                // On crée le HTML en string (beaucoup plus rapide que .clone())
                const newRowHtml = `
                <tr class="product-row">
                    <td>
                        <select name="products[${index}][product_id]" class="form-select product-select" required>
                            ${originalOptionsHtml}
                        </select>
                    </td>
                    <td><input type="number" name="products[${index}][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                    <td><input type="text" class="form-control price-display" readonly value="0.00 €"></td>
                    <td><input type="text" class="form-control subtotal-display" readonly value="0.00 €"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
                </tr>`;

                const $newRow = $(newRowHtml);

                // On retire les options déjà prises AVANT d'injecter dans le DOM
                selectedIds.forEach(id => {
                    if (id) $newRow.find(`option[value="${id}"]`).remove();
                });

                productList.append($newRow);
                initS2($newRow.find('.product-select'));
                $newRow.find('.product-select').select2('open');
            });

            // --- DÉLÉGATION D'ÉVÉNEMENTS (Un seul écouteur pour toute la table) ---
            productList.on('change', '.product-select', function() {
                const row = $(this).closest('tr');
                const price = $(this).find(':selected').data('price') || 0;
                row.find('.price-display').val(parseFloat(price).toFixed(2) + ' €');
                fastUpdate();
            });

            productList.on('input', '.qty-input', fastUpdate);
            $('#discount-input').on('input', fastUpdate);

            productList.on('click', '.remove-row', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('tr').remove();
                    fastUpdate();
                }
            });

            // Init première ligne
            initS2($('.product-select'));
            $('#reset-cart').on('click', function() {
                if (confirm('Voulez-vous vider le panier ?')) {
                    $('.product-row').not(':first').remove(); // Garde une ligne
                    $('.product-select').val('').trigger('change');
                    $('.qty-input').val(1);
                    fastUpdate();
                }
            });
        });
        $(document).on('keydown', function(e) {
            // F2 pour ajouter un produit
            if (e.key === "F2") {
                $('#add-product').click();
            }
        });
    </script>
@endpush
