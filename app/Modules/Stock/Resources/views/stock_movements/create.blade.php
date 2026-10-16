@extends('layouts.app-back-office')

@section('title', 'Stock correction')
@section('subtitle', 'Fallback in case of a mistake: purchases and sales update the stock automatically.')

@php
    $oldId = (int) old('product_color_id', $selectedId);
    $selected = $oldId ? $productColors->firstWhere('id', $oldId) : null;
@endphp

@section('content')
    <x-ui.errors />

    <x-ui.alert tone="warning" title="Use only to fix a mistake" class="mb-3">
        Stock increases when a purchase is received and decreases with each sale. Record a correction only for
        a broken or lost item, a counting error or a return. For a full count, use a stock count instead.
    </x-ui.alert>

    <div class="row g-3">
        {{-- Choix de la variante --}}
        <div class="col-lg-5">
            <x-ui.card title="1. Select the variant" flush>
                <x-slot:toolbar>
                    <x-ui.search name="" id="search-product" placeholder="Search product, variant or SKU..." />
                </x-slot:toolbar>

                <div class="list-group list-group-flush position-relative" id="product-results" style="max-height: 460px; overflow-y: auto;">
                    @foreach ($productColors as $item)
                        <button type="button" @class(['list-group-item list-group-item-action product-item', 'active' => $selected?->id === $item->id])
                            data-id="{{ $item->id }}" data-name="{{ $item->label }}" data-stock="{{ $item->stock }}">
                            <span class="d-flex justify-content-between align-items-center gap-2">
                                <span>{{ $item->label }}</span>
                                <span class="text-nowrap small">Stock <strong>{{ $item->stock }}</strong></span>
                            </span>
                            <span class="ui-cell-meta">{{ $item->sku }}</span>
                        </button>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        {{-- Saisie de la correction --}}
        <div class="col-lg-7">
            <form action="{{ route('admin.movements.store') }}" method="POST" id="movement-form">
                @csrf
                <input type="hidden" name="product_color_id" id="selected-product-id" value="{{ $selected?->id }}" required>

                <x-ui.card title="2. Correction">
                    <div class="ui-field">
                        <div class="form-label">Selected variant</div>
                        <div id="selected-product-display" @class(['alert mb-0 py-2', 'alert-info' => $selected, 'alert-secondary' => !$selected])>
                            @if ($selected)
                                {{ $selected->label }} — current stock: {{ $selected->stock }}
                            @else
                                Select a variant in the list.
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <x-ui.field class="col-md-6" name="type" label="Correction" required>
                            <select name="type" id="type" @class(['form-select', 'is-invalid' => $errors->has('type')]) required>
                                <option value="" disabled @selected(!old('type'))>Choose...</option>
                                <option value="in" @selected(old('type') === 'in')>Add to stock</option>
                                <option value="out" @selected(old('type') === 'out')>Remove from stock</option>
                            </select>
                        </x-ui.field>
                        <x-ui.field class="col-md-6" name="quantity" label="Quantity" type="number" min="1" required
                            placeholder="e.g. 2" :value="old('quantity')" />
                    </div>

                    <x-ui.field class="mb-0" name="reason" label="Reason" type="textarea" rows="3" required minlength="5" maxlength="240"
                        placeholder="e.g. 2 items broken during delivery, counting error on 12/10..."
                        hint="Kept in the stock history as « Correction : … »." :value="old('reason')" />

                    <x-slot:footer>
                        <div class="ui-form-actions">
                            <a href="{{ route('admin.movements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submit-btn" @disabled(!$selected)
                                data-confirm="Record this stock correction?" data-confirm-ok="Record"
                                data-confirm-text="The stock changes immediately and the correction stays in the history.">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Record correction
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
            $('#selected-product-display')
                .removeClass('alert-secondary').addClass('alert-info')
                .text(`${$(this).data('name')} — current stock: ${$(this).data('stock')}`);
            $('#submit-btn').prop('disabled', false);
        });

        // Variante présélectionnée (lien depuis la fiche produit) : amenée en vue dans la liste
        const preselected = document.querySelector('.product-item.active');
        if (preselected) {
            document.getElementById('product-results').scrollTop = preselected.offsetTop - 120;
        }
    </script>
@endpush
