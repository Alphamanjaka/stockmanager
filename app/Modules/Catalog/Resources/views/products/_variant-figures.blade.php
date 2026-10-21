{{--
    Prix et seuil d'alerte d'une variante (fenêtres « Nouvelle variante » et « Modifier la variante »).
    Le stock n'est pas saisi ici : une variante démarre à 0, puis évolue par les mouvements de stock
    (achat reçu, vente, inventaire, correction). En modification, il est seulement affiché.
    Variables : $product, $prefix ('add' : valeurs par défaut ; 'edit' : rempli en JavaScript depuis la ligne choisie)
    En ajout, avec une marge par défaut (Paramètres), le prix de vente suit le prix d'achat (data-pricing-*, voir
    resources/js/price-suggestion.js) ; en modification, le prix existant n'est jamais recalculé.
--}}
@php
    $currency = settings('currency_symbol', 'MGA');
    $add = $prefix === 'add';
    $margin = $add ? \App\Modules\Catalog\Support\Pricing::defaultMargin() : null;
@endphp

<div class="col-md-6 ui-field">
    <label class="form-label required" for="{{ $prefix }}-variant-price-purchase">Purchase price</label>
    <div class="input-group">
        <input type="number" name="price_purchase" id="{{ $prefix }}-variant-price-purchase" class="form-control"
            step="0.01" min="0" placeholder="0.00" required @if ($add) data-pricing-cost @endif>
        <span class="input-group-text">{{ $currency }}</span>
    </div>
    <div class="form-text">Updated at each received purchase (weighted average cost).</div>
</div>
<div class="col-md-6 ui-field">
    <label class="form-label required" for="{{ $prefix }}-variant-price">Selling price</label>
    <div class="input-group">
        <input type="number" name="price" id="{{ $prefix }}-variant-price" class="form-control" step="0.01" min="0"
            @if ($add) value="{{ $product->price }}" data-pricing-price @endif required>
        <span class="input-group-text">{{ $currency }}</span>
    </div>
    @if ($margin !== null)
        <div class="form-text">
            Filled in from the purchase price + {{ rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.') }} % (default margin). You can change it.
        </div>
    @endif
</div>
<div class="col-md-6 ui-field mb-md-0">
    <label class="form-label" for="{{ $prefix }}-variant-alert">Alert threshold</label>
    <input type="number" name="alert_stock" id="{{ $prefix }}-variant-alert" class="form-control" min="0"
        @if ($add) value="5" @endif>
    <div class="form-text">An alert is raised when the stock reaches this level.</div>
</div>
<div class="col-md-6 ui-field mb-0">
    {{-- Affiché seulement : pas de name, la valeur n'est jamais envoyée --}}
    <label class="form-label" for="{{ $prefix }}-variant-stock">Stock</label>
    <input type="text" id="{{ $prefix }}-variant-stock" class="form-control" readonly tabindex="-1"
        @if ($add) value="0" @endif>
    <div class="form-text">
        {{ $add ? 'A new variant starts at 0.' : 'Read-only.' }}
        It changes with received purchases and sales.
    </div>
</div>
