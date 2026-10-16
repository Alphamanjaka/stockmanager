{{--
    Champ de formulaire uniforme : libellé (astérisque si requis), contrôle, aide, message d'erreur.

        <x-ui.field name="email" label="Email" type="email" :value="old('email', $customer->email)" />
        <x-ui.field name="notes" label="Notes" type="textarea" rows="3" :value="old('notes')" />
        <x-ui.field name="category_id" label="Category" required>
            <select id="category_id" name="category_id" class="form-select">…</select>   (contrôle libre)
        </x-ui.field>

    class s'applique au conteneur (ex. col-md-6) ; les autres attributs (placeholder, maxlength, step…) au contrôle.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'id' => null,
])

@php
    $id ??= $name;
    // options[0][name] → options.0.name : clé de l'erreur de validation
    $errorKey = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $invalid = $errors->has($errorKey);
@endphp

<div {{ $attributes->only('class')->class('ui-field') }}>
    @if ($label)
        <label for="{{ $id }}" @class(['form-label', 'required' => $required])>{{ $label }}</label>
    @endif

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @class(['form-control', 'is-invalid' => $invalid]) @required($required)
            {{ $attributes->except('class') }}>{{ $value }}</textarea>
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" @if ($type !== 'file') value="{{ $value }}" @endif
            @class(['form-control', 'is-invalid' => $invalid]) @required($required) {{ $attributes->except('class') }}>
    @endif

    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif

    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
