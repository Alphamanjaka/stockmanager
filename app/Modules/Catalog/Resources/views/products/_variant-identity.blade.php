{{--
    Identité d'une variante : une valeur par option du produit, et la marque.
    Variables : $product, $brands, $prefix ('add' ou 'edit', pour des identifiants uniques)
--}}
@forelse ($product->options ?? [] as $option)
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ $option['name'] }}</label>
        <input type="text" name="values[{{ $option['name'] }}]" class="form-control variant-option-input"
            data-option-name="{{ $option['name'] }}" list="{{ $prefix }}-option-{{ $loop->index }}" required maxlength="100"
            placeholder="{{ implode(', ', $option['values']) }}">
        <datalist id="{{ $prefix }}-option-{{ $loop->index }}">
            @foreach ($option['values'] as $value)
                <option value="{{ $value }}">
            @endforeach
        </datalist>
    </div>
@empty
    <div class="col-12 mb-3">
        <div class="alert alert-light border small mb-0">This product has no options: it has a single default variant.</div>
    </div>
@endforelse

<div class="col-md-6 mb-4">
    <label class="form-label fw-bold text-muted small text-uppercase mb-2">Brand</label>
    <select name="brand_id" class="form-select variant-brand-select">
        <option value="">—</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
        @endforeach
    </select>
</div>

@if (!empty($product->options))
    <div class="col-12 mb-3 small text-muted">
        A new value (e.g. a new volume) is added to the product's options automatically.
    </div>
@endif
