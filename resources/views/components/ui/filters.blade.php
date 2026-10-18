{{--
    Recherche et filtres d'une liste (formulaire GET), à placer dans la barre d'outils d'une carte :

        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.brands.index')">
                <x-ui.search placeholder="Search brand..." />
                <select name="category" class="form-select ui-filter" aria-label="Category">…</select>
            </x-ui.filters>
        </x-slot:toolbar>

    Le tri en cours est conservé ; « Reset » n'apparaît que si un filtre est actif.
--}}
@props(['action'])

@php
    $active = collect(request()->except(['page', 'sort', 'order', '_token']))->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

<form method="GET" action="{{ $action }}" role="search" {{ $attributes->class('ui-filters') }}>
    {{ $slot }}

    @foreach (request()->only(['sort', 'order']) as $key => $value)
        @if (is_string($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="ui-filters-actions">
        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-funnel" aria-hidden="true"></i> Filter
        </button>
        @if ($active)
            <a href="{{ $action }}" class="btn btn-link">Reset</a>
        @endif
    </div>
</form>
