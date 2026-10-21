{{--
    Menu d'actions secondaires : un seul bouton au lieu d'une rangée de boutons.
    En-tête de page : engrenage (actions de la page) ; ligne de tableau (row) : « ⋮ » (actions de la ligne).
    Contenu : <x-ui.menu-item>, séparateur <li><hr class="dropdown-divider"></li>, titre <li><h6 class="dropdown-header">.

        @section('actions')
            <x-ui.actions-menu>
                <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">Import</x-ui.menu-item>
            </x-ui.actions-menu>
            <a href="…" class="btn btn-primary">New product</a>
        @endsection

        <x-ui.actions-menu row>…actions de la ligne…</x-ui.actions-menu>

    Positionnement « fixed » : le menu d'une ligne n'est pas coupé par le défilement du tableau (.table-responsive).
--}}
@props(['row' => false, 'label' => null, 'icon' => null])

@php
    $label ??= $row ? __('Row actions') : __('More actions');
    $icon ??= $row ? 'bi-three-dots-vertical' : 'bi-gear';
@endphp

<div {{ $attributes->class(['dropdown ui-actions-menu']) }}>
    <button type="button" @class(['btn', 'btn-icon' => $row, 'btn-outline-secondary ui-actions-toggle' => !$row])
        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
        title="{{ $label }}" aria-label="{{ $label }}">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        {{ $slot }}
    </ul>
</div>
