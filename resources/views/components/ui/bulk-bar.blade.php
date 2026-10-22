{{--
    Sélection multiple d'une liste : barre d'actions affichée dès qu'une case est cochée (resources/js/bulk-select.js).
    À placer dans une carte marquée data-bulk, avec <x-ui.bulk-check-all> et <x-ui.bulk-check> dans le tableau.

        <x-ui.card flush data-bulk>
            <x-ui.bulk-bar export="brands" :archive="route('admin.brands.archive')" :restore="route('admin.brands.restore')" />
            <table>…<x-ui.bulk-check-all />…<x-ui.bulk-check :value="$brand->id" :label="$brand->name" />…</table>
        </x-ui.card>

    export  : clé de l'export (PDF / Excel de la sélection)
    archive, restore : routes d'archivage (ArchivesRecords) ; l'une ou l'autre selon la liste affichée (actifs ou archives)
    Contenu : actions propres à la liste (<x-ui.bulk-action>).
--}}
@props(['export' => null, 'archive' => null, 'restore' => null])

@php
    $archived = request('status') === 'archived';
    $query = collect(request()->except(['page', 'ids']))->filter(fn ($value) => is_string($value) && $value !== '')->all();
@endphp

<div class="ui-bulk-bar" data-bulk-bar hidden>
    <span class="ui-bulk-count" aria-live="polite">{!! __(':count selected', ['count' => '<strong data-bulk-count>0</strong>']) !!}</span>

    <div class="ui-bulk-actions">
        @if ($archive && !$archived)
            <x-ui.bulk-action :action="$archive" icon="bi-archive" :confirm="__('Archive :count item(s)?')"
                :confirm-text="__('They will be hidden from lists and forms, with their history kept. You can restore them at any time.')">
                {{ __('Archive') }}
            </x-ui.bulk-action>
        @endif
        @if ($restore && $archived)
            <x-ui.bulk-action :action="$restore" icon="bi-arrow-counterclockwise" :confirm="__('Restore :count item(s)?')">
                {{ __('Restore') }}
            </x-ui.bulk-action>
        @endif

        {{ $slot }}

        @if ($export)
            <a href="{{ route('admin.exports.download', ['list' => $export, 'format' => 'pdf'] + $query) }}"
                class="btn btn-sm btn-outline-secondary" data-bulk-link>
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
            </a>
            <a href="{{ route('admin.exports.download', ['list' => $export, 'format' => 'xlsx'] + $query) }}"
                class="btn btn-sm btn-outline-secondary" data-bulk-link>
                <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Excel
            </a>
        @endif
    </div>

    <button type="button" class="btn btn-sm btn-link ms-auto" data-bulk-clear>
        {{ __('Clear selection') }} <kbd>{{ __('pos.keys.esc') }}</kbd>
    </button>
</div>
