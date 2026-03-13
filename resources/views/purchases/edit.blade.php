@extends('layouts.app-back-office')
@section('title', 'edit purchase #' . $purchase->reference)

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit a Purchase #{{ $purchase->reference }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.purchases.update', $purchase->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-select">
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="purchase_date" class="form-label">Date d'achat</label>
                                <input type="text" id="purchase_date" class="form-control" readonly
                                    value="{{ $purchase->created_at->format('d/m/Y H:i') }}">
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                            <h5>Produits de la commande</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-item-btn">
                                <i class="fas fa-plus"></i> Ajouter un produit
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 40%">Produit</th>
                                        <th style="width: 15%">Quantité</th>
                                        <th style="width: 20%">Prix Unitaire</th>
                                        <th style="width: 20%">Sous-total</th>
                                        <th style="width: 5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="items-container">
                                    @foreach ($purchase->items as $index => $item)
                                        <tr class="item-row">
                                            <td>
                                                <select name="products[{{ $index }}][product_id]"
                                                    class="form-select product-select" required>
                                                    <option value="">Sélectionner un produit</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}"
                                                            {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }} (Stock: {{ $product->quantity_stock }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="products[{{ $index }}][quantity]"
                                                    class="form-control text-center quantity-input"
                                                    value="{{ $item->quantity }}" min="1" required>
                                            </td>
                                            <td>
                                                <input type="number" name="products[{{ $index }}][unit_price]"
                                                    class="form-control text-end price-input"
                                                    value="{{ $item->unit_price }}" step="0.01" min="0" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end subtotal-display"
                                                    value="{{ number_format($item->subtotal, 2, '.', '') }}" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total Net :</td>
                                        <td class="text-end fw-bold">
                                            <span
                                                id="total-amount">{{ number_format($purchase->total_net, 2, '.', '') }}</span>
                                            <small>MGA</small>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.purchases.show', $purchase->id) }}"
                                class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-success px-4">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Template caché pour les nouvelles lignes (Javascript) --}}
    <template id="row-template">
        <tr class="item-row">
            <td>
                <select name="products[INDEX][product_id]" class="form-select product-select" required>
                    <option value="">Sélectionner un produit</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                            {{ $product->name }} (Stock: {{ $product->quantity_stock }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="products[INDEX][quantity]" class="form-control text-center quantity-input"
                    value="1" min="1" required>
            </td>
            <td>
                <input type="number" name="products[INDEX][unit_price]" class="form-control text-end price-input"
                    value="0.00" step="0.01" min="0" required>
            </td>
            <td>
                <input type="text" class="form-control text-end subtotal-display" value="0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn">&times;</button>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemIndex = {{ $purchase->items->count() }};
            const container = document.getElementById('items-container');
            const template = document.getElementById('row-template');
            const totalDisplay = document.getElementById('total-amount');

            // Ajouter une ligne
            document.getElementById('add-item-btn').addEventListener('click', function() {
                const clone = template.content.cloneNode(true);

                // Remplacer l'index temporaire par un index unique
                const inputs = clone.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.name = input.name.replace('INDEX', itemIndex);
                });

                container.appendChild(clone);
                itemIndex++;
                updateTotal();
            });

            // Supprimer une ligne (délégation d'événement)
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-row-btn') || e.target.closest('.remove-row-btn')) {
                    const row = e.target.closest('tr');
                    if (container.querySelectorAll('tr').length > 1) {
                        row.remove();
                        updateTotal();
                    } else {
                        alert("La commande doit contenir au moins un produit.");
                    }
                }
            });

            // Calculs dynamiques (quantité, prix, produit)
            container.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input') || e.target.classList.contains(
                        'price-input')) {
                    updateRowSubtotal(e.target.closest('tr'));
                }
            });

            // Mise à jour du prix lors de la sélection d'un produit
            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('product-select')) {
                    const option = e.target.options[e.target.selectedIndex];
                    const price = option.getAttribute('data-price');
                    const row = e.target.closest('tr');
                    // On met un prix par défaut (prix de vente ou 0) si le champ est vide ou à 0
                    const priceInput = row.querySelector('.price-input');
                    if (price && (priceInput.value == 0 || priceInput.value == '')) {
                        priceInput.value = price;
                    }
                    updateRowSubtotal(row);
                }
            });

            function updateRowSubtotal(row) {
                const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const subtotal = qty * price;
                row.querySelector('.subtotal-display').value = subtotal.toFixed(2);
                updateTotal();
            }

            function updateTotal() {
                let total = 0;
                container.querySelectorAll('.subtotal-display').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                totalDisplay.textContent = total.toFixed(2);
            }
        });
    </script>
@endsection
