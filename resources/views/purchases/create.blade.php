@extends('layouts.app-back-office')

@section('title', 'Achats')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchase-form">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold">Sélectionner le Fournisseur</label>
                        <select name="supplier_id" class="form-select select2" required>
                            <option value="">-- Choisir un fournisseur --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white fw-bold">Ajouter des Produits à l'Approvisionnement</div>
                    <div class="card-body">
                        <table class="table" id="purchase-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix d'Achat Unitaire</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr class="product-row">
                                    <td>
                                        <select name="products[0][product_id]" class="form-select product-select" required>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="products[0][quantity]" class="form-control qty-input"
                                            min="1" required>
                                    </td>
                                    <td><input type="number" name="products[0][unit_cost]"
                                            class="form-control unit-price-input" step="0.01" required>
                                    </td>
                                    <td><button type="button" class="btn btn-danger remove-row">X</button></td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-primary" id="add-product">+ Ajouter un produit</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white fw-bold">Résumé de la transaction</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Brut :</span>
                            <span id="display-brut" class="fw-bold">0.00 €</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Remise (€)</label>
                            <input type="number" name="discount" id="discount-input" class="form-control" value="0"
                                min="0">
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total Net :</span>
                            <span id="display-net" class="h5 text-success fw-bold">0.00 €</span>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg shadow">
                            Valider la transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            // --- CONSTANTES ET VARIABLES ---
            const productList = $('#product-list');
            const discountInput = $('#discount-input');
            const addProductBtn = $('#add-product');
            const purchaseForm = $('#purchase-form');
            const submitButton = purchaseForm.find('button[type="submit"]');
            // On clone la première ligne pour s'en servir de modèle
            const productRowTemplate = productList.find('.product-row').first().clone(true);

            // --- FONCTIONS ---

            /**
             * Initialise Select2 sur un élément select.
             * @param {jQuery} element L'élément select à initialiser.
             */
            function initSelect2(element) {
                element.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Chercher un produit...',
                    width: '100%'
                });
            }

            /**
             * Met à jour les produits disponibles dans les listes déroulantes pour éviter les doublons.
             */
            function updateAvailableProducts() {
                const selectedIds = $('.product-select').map((_, el) => $(el).val()).get();

                $('.product-select').each(function() {
                    const currentSelectedId = $(this).val();
                    $(this).find('option').each(function() {
                        const option = $(this);
                        const optionValue = option.val();

                        // Désactive une option si elle est sélectionnée dans une AUTRE ligne.
                        if (optionValue && selectedIds.includes(optionValue) && optionValue !==
                            currentSelectedId) {
                            option.prop('disabled', true);
                        } else {
                            option.prop('disabled', false);
                        }
                    });
                });

                // Rafraîchir l'affichage de chaque Select2 pour prendre en compte les options désactivées.
                $('.product-select').each(function() {
                    $(this).select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Chercher un produit...',
                        width: '100%'
                    });
                });
            }

            /**
             * Calcule tous les totaux (lignes et résumé) et met à jour l'état du formulaire.
             */
            function updateFormState() {
                let totalBrut = 0;
                let hasProducts = false;

                // 1. Mise à jour de chaque ligne de produit
                $('.product-row').each(function() {
                    const row = $(this);
                    const select = row.find('.product-select');
                    const qtyInput = row.find('.qty-input');
                    const priceInput = row.find('.unit-price-input');

                    if (select.val()) {
                        hasProducts = true;
                    }

                    const qty = parseFloat(qtyInput.val() || 0);
                    const price = parseFloat(priceInput.val() || 0);

                    const subtotal = price * qty;
                    totalBrut += subtotal;
                });

                // 2. Mise à jour du résumé de la transaction
                const discount = parseFloat(discountInput.val() || 0);
                const totalNet = Math.max(0, totalBrut - discount);

                $('#display-brut').text(totalBrut.toFixed(2) + ' €');
                $('#display-net').text(totalNet.toFixed(2) + ' €');

                // 3. Gestion de l'état global (avertissement et bouton de soumission)
                submitButton.prop('disabled', !hasProducts);

                // 4. Mettre à jour les produits sélectionnables pour éviter les doublons
                updateAvailableProducts();
            }

            // --- GESTION DES ÉVÉNEMENTS ---

            // Initialisation au chargement de la page
            initSelect2($('.product-select'));
            updateFormState(); // Calcul initial

            // Recalculer à chaque changement sur une ligne ou sur la remise
            productList.on('change', '.product-select', updateFormState);
            productList.on('input', '.qty-input', updateFormState);
            productList.on('input', '.unit-price-input', updateFormState);
            discountInput.on('input', updateFormState);

            // Ajout d'une nouvelle ligne de produit
            addProductBtn.on('click', function() {
                const index = Date.now(); // Index unique basé sur le timestamp
                const newRow = productRowTemplate.clone(true);

                // Nettoyage du clone pour éviter les conflits
                newRow.find('.select2-container').remove();
                newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id')
                    .show();
                newRow.find('select, input').each(function() {
                    const el = $(this);
                    const name = el.attr('name');
                    if (name) {
                        el.attr('name', name.replace(/\[.*?\]/, `[${index}]`));
                    }
                    el.removeClass('is-invalid');
                    if (el.hasClass('qty-input')) {
                        el.val(1);
                    } else {
                        el.val('');
                    }
                });

                productList.append(newRow);
                const newSelect = newRow.find('.product-select');
                initSelect2(newSelect);
                updateFormState();

                // Amélioration UX : ouvrir le select pour une saisie rapide
                newSelect.select2('open');
            });

            // Suppression d'une ligne de produit
            productList.on('click', '.remove-row', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('tr').remove();
                    updateFormState(); // Recalculer et mettre à jour les options
                }
            });
        });
    </script>
@endsection
