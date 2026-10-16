@extends('layouts.app-back-office')

@section('title', 'New stock movement')

@section('content')
    <x-ui.errors />

    <div class="row g-3">
        {{-- Choix de la variante --}}
        <div class="col-lg-5">
            <x-ui.card title="1. Select a product variant" flush>
                <x-slot:toolbar>
                    <x-ui.search name="" id="search-product" placeholder="Search product, variant or SKU..." />
                </x-slot:toolbar>

                <div class="list-group list-group-flush" id="product-results" style="max-height: 460px; overflow-y: auto;">
                    @foreach ($productColors as $item)
                        <button type="button" class="list-group-item list-group-item-action product-item d-flex justify-content-between align-items-center gap-2"
                            data-id="{{ $item->id }}" data-name="{{ $item->label }}">
                            <span>{{ $item->label }}</span>
                            <span class="ui-tag">{{ $item->sku }}</span>
                        </button>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        {{-- Saisie du mouvement --}}
        <div class="col-lg-7">
            <form action="{{ route('admin.movements.store') }}" method="POST" id="movement-form">
                @csrf
                <input type="hidden" name="product_color_id" id="selected-product-id" required>

                <x-ui.card title="2. Movement details">
                    <div class="ui-field">
                        <div class="form-label">Selected variant</div>
                        <div id="selected-product-display" class="alert alert-secondary mb-0 py-2">
                            Select a variant in the list.
                        </div>
                    </div>

                    <div class="row">
                        <x-ui.field class="col-md-6" name="type" label="Movement type" required>
                            <select name="type" id="type" class="form-select" required>
                                <option value="" selected disabled>Choose...</option>
                                <option value="in">Stock in (increase)</option>
                                <option value="out">Stock out (decrease)</option>
                            </select>
                        </x-ui.field>
                        <x-ui.field class="col-md-6" name="quantity" label="Quantity" type="number" min="1" required
                            placeholder="Enter amount" />
                    </div>

                    <x-ui.field class="mb-0" name="reason" label="Reason" type="textarea" rows="3" maxlength="255"
                        placeholder="Optional: damage, return, adjustment..." />

                    <x-slot:footer>
                        <div class="ui-form-actions">
                            <a href="{{ route('admin.movements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Record movement
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
        // Recherche locale dans la liste des variantes
        $('#search-product').on('input', function () {
            const value = $(this).val().toLowerCase();
            $('#product-results .product-item').each(function () {
                $(this).toggleClass('d-none', !$(this).text().toLowerCase().includes(value));
            });
        });

        // Choix d'une variante
        $('.product-item').on('click', function () {
            $('.product-item').removeClass('active');
            $(this).addClass('active');

            $('#selected-product-id').val($(this).data('id'));
            $('#selected-product-display').removeClass('alert-secondary').addClass('alert-info').text($(this).data('name'));
            $('#submit-btn').prop('disabled', false);
        });
    </script>
@endpush
