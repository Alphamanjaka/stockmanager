{{--
    Prix et stock d'une variante (fenêtres « Nouvelle variante » et « Modifier la variante »).
    Variables : $product, $prefix ('add' : valeurs par défaut ; 'edit' : rempli en JavaScript depuis la ligne choisie)
--}}
@php
    $currency = settings('currency_symbol', 'MGA');
    $add = $prefix === 'add';
@endphp

<div class="col-md-6 ui-field">
    <label class="form-label required" for="{{ $prefix }}-variant-price-purchase">Purchase price</label>
    <div class="input-group">
        <input type="number" name="price_purchase" id="{{ $prefix }}-variant-price-purchase" class="form-control"
            step="0.01" min="0" placeholder="0.00" required>
        <span class="input-group-text">{{ $currency }}</span>
    </div>
</div>
<div class="col-md-6 ui-field">
    <label class="form-label required" for="{{ $prefix }}-variant-price">Selling price</label>
    <div class="input-group">
        <input type="number" name="price" id="{{ $prefix }}-variant-price" class="form-control" step="0.01" min="0"
            @if ($add) value="{{ $product->price }}" @endif required>
        <span class="input-group-text">{{ $currency }}</span>
    </div>
</div>
<div class="col-md-6 ui-field mb-md-0">
    <label class="form-label required" for="{{ $prefix }}-variant-stock">{{ $add ? 'Initial stock' : 'Current stock' }}</label>
    <input type="number" name="stock" id="{{ $prefix }}-variant-stock" class="form-control" min="0"
        @if ($add) value="0" @endif required>
</div>
<div class="col-md-6 ui-field mb-0">
    <label class="form-label" for="{{ $prefix }}-variant-alert">Alert threshold</label>
    <input type="number" name="alert_stock" id="{{ $prefix }}-variant-alert" class="form-control" min="0"
        @if ($add) value="5" @endif>
    <div class="form-text">An alert is raised when the stock reaches this level.</div>
</div>
