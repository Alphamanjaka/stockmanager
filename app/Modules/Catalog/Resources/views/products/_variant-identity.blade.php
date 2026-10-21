{{--
    Identité d'une variante : une valeur par option du produit, et la marque.
    Variables : $product, $brands, $prefix ('add' ou 'edit', pour des identifiants uniques)
--}}
@forelse ($product->options ?? [] as $option)
    <div class="col-md-6 ui-field">
        <label class="form-label required" for="{{ $prefix }}-option-input-{{ $loop->index }}">{{ $option['name'] }}</label>
        <input type="text" id="{{ $prefix }}-option-input-{{ $loop->index }}" name="values[{{ $option['name'] }}]"
            class="form-control variant-option-input" data-option-name="{{ $option['name'] }}"
            list="{{ $prefix }}-option-{{ $loop->index }}" required maxlength="100"
            placeholder="{{ implode(', ', $option['values']) }}">
        <datalist id="{{ $prefix }}-option-{{ $loop->index }}">
            @foreach ($option['values'] as $value)
                <option value="{{ $value }}">
            @endforeach
        </datalist>
    </div>
@empty
    <div class="col-12">
        <x-ui.alert tone="secondary">{{ __('This product has no options: it has a single default variant.') }}</x-ui.alert>
    </div>
@endforelse

<div class="col-md-6 ui-field">
    <label class="form-label" for="{{ $prefix }}-variant-brand">{{ __('Brand') }}</label>
    <select name="brand_id" id="{{ $prefix }}-variant-brand" class="form-select variant-brand-select">
        <option value="">—</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
        @endforeach
    </select>
</div>

@if (!empty($product->options))
    <div class="col-12 form-text mt-0 mb-3">
        {{ __("A new value (e.g. a new volume) is added to the product's options automatically.") }}
    </div>
@endif
