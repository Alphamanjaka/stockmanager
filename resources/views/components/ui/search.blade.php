{{--
    Champ de recherche avec loupe. Sans name (name=""), il sert de filtre local en JavaScript (donner un id).
    La valeur par défaut est celle de la requête (?search=...).
--}}
@props(['name' => 'search', 'placeholder' => null, 'value' => null])

@php $placeholder ??= __('Search...'); @endphp

<div {{ $attributes->only('class')->class('ui-search') }}>
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" @if ($name) name="{{ $name }}" @endif class="form-control" placeholder="{{ $placeholder }}"
        aria-label="{{ $placeholder }}" value="{{ $value ?? ($name ? request($name) : '') }}" autocomplete="off"
        {{ $attributes->except('class') }}>
</div>
