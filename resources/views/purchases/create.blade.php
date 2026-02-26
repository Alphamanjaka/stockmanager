@extends('layouts.app-back-office')

@section('title', 'New Purchase')
@section('content')
    <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchase-form">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body">
                        <label class="form-label fw-bold">Select a Supplier</label>
                        <select name="supplier_id" id="supplier-select" class="form-select" required>
                            <option value="">-- Choose a supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white fw-bold">Add Products to Purchase</div>
                    <div class="card-body">
                        <table class="table align-middle" id="purchase-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost (Mga)</th>
                                    <th>Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr class="product-row">
                                    <td>
                                        <select name="products[0][product_id]" class="form-select product-select" required>
                                            <option value="">Choose product...</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="products[0][quantity]" class="form-control qty-input"
                                            min="1" value="1" required></td>
                                    <td><input type="number" name="products[0][unit_price]"
                                            class="form-control price-input" step="0.01" value="0" required></td>
                                    <td><input type="text" class="form-control subtotal-display" readonly
                                            value="0.00 Mga"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i
                                                class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-primary btn-sm" id="add-product">
                            <i class="bi bi-plus-circle"></i> Add a Product
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm sticky-top border-0" style="top: 20px;">
                    <div class="card-header bg-primary text-white fw-bold">Purchase Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Gross :</span>
                            <span id="display-brut" class="fw-bold">0.00 Mga</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Discount (Mga)</label>
                            <input type="number" name="discount" id="discount-input" class="form-control" value="0"
                                min="0">
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5 text-primary">Total Net :</span>
                            <span id="display-net" class="h5 text-primary fw-bold">0.00 Mga</span>
                        </div>
                        <button type="submit" class="btn btn-success w-100 btn-lg shadow-sm" id="btn-submit">
                            Validate Purchase
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
            const originalOptions = $('.product-select').first().html();

            function initS2(el) {
                el.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Search product...'
                });
            }

            $('#supplier-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            function fastUpdate() {
                let totalBrut = 0;
                const selectedIds = [];

                $('.product-row').each(function() {
                    const row = $(this);
                    const select = row.find('.product-select');
                    const val = select.val();

                    if (val) {
                        selectedIds.push(val);
                        const qty = parseFloat(row.find('.qty-input').val() || 0);
                        const cost = parseFloat(row.find('.price-input').val() || 0);
                        const subtotal = qty * cost;
                        totalBrut += subtotal;

                        row.find('.subtotal-display').val(subtotal.toLocaleString('fr-FR', {
                            minimumFractionDigits: 2
                        }) + ' Mga');
                    }
                });

                const discount = parseFloat($('#discount-input').val() || 0);
                const totalNet = Math.max(0, totalBrut - discount);

                $('#display-brut').text(totalBrut.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2
                }) + ' Mga');
                $('#display-net').text(totalNet.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2
                }) + ' Mga');

                $('#btn-submit').prop('disabled', selectedIds.length === 0);
                return selectedIds;
            }

            $('#add-product').on('click', function() {
                const selectedIds = fastUpdate();
                const index = Date.now();

                const newRow = $(`
                <tr class="product-row">
                    <td><select name="products[${index}][product_id]" class="form-select product-select" required>${originalOptions}</select></td>
                    <td><input type="number" name="products[${index}][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                    <td><input type="number" name="products[${index}][unit_price]" class="form-control price-input" step="0.01" value="0" required></td>
                    <td><input type="text" class="form-control subtotal-display" readonly value="0.00 Mga"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
                </tr>`);

                // Retirer les produits déjà sélectionnés
                selectedIds.forEach(id => {
                    if (id) newRow.find(`option[value="${id}"]`).remove();
                });

                productList.append(newRow);
                const newSelect = newRow.find('.product-select');
                initS2(newSelect);

                // Ouvre le select et permet de taper immédiatement
                setTimeout(() => {
                    newSelect.select2('open');
                }, 100);
            });

            // --- RACCOURCIS CLAVIER ---
            $(document).on('keydown', function(e) {
                // F2 pour ajouter une ligne
                if (e.key === "F2") {
                    e.preventDefault();
                    $('#add-product').click();
                }
                // Entrée dans le dernier champ prix passe à la ligne suivante
                if (e.key === "Enter" && $(e.target).hasClass('price-input')) {
                    e.preventDefault();
                    $('#add-product').click();
                }
            });

            // Event Delegation
            productList.on('change', '.product-select', fastUpdate);
            productList.on('input', '.qty-input, .price-input', fastUpdate);
            $('#discount-input').on('input', fastUpdate);

            productList.on('click', '.remove-row', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('tr').remove();
                    fastUpdate();
                }
            });

            initS2($('.product-select'));
            // Alerte avant de quitter si le panier n'est pas vide
            let isDirty = false;
            $(document).on('change', 'input, select', () => isDirty = true);

            $(window).on('beforeunload', function() {
                if (isDirty && $('.product-row').length > 1) {
                    return "You have unsaved changes. Are you sure you want to leave?";
                }
            });

            $('#purchase-form').on('submit', () => isDirty = false);
            $('#purchase-form').on('submit', function(e) {
                e.preventDefault();
                const total = $('#display-net').text();

                Swal.fire({
                    title: 'Confirm Purchase?',
                    text: `Total amount is ${total}. This will update your stock levels.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Yes, validate!',
                    cancelButtonText: 'Review'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
@endpush
