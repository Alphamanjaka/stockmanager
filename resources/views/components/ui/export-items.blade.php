{{--
    Éléments « Export » d'un <x-ui.actions-menu> : la liste affichée (mêmes filtres, même tri) en PDF ou en Excel.
    list : clé de l'export déclarée par le module (propriété $exports du provider, ex. 'products').

        <x-ui.actions-menu>
            <x-ui.export-items list="products" />
        </x-ui.actions-menu>

    data-export-link : liens mis à jour par une page dont les filtres sont appliqués en JavaScript (achats).
--}}
@props(['list'])

@php $query = collect(request()->except(['page', 'ids']))->filter(fn ($value) => is_string($value) && $value !== '')->all(); @endphp

<li><h6 class="dropdown-header">Export</h6></li>
<x-ui.menu-item :href="route('admin.exports.download', ['list' => $list, 'format' => 'pdf'] + $query)" icon="bi-file-earmark-pdf"
    data-export-link>PDF document</x-ui.menu-item>
<x-ui.menu-item :href="route('admin.exports.download', ['list' => $list, 'format' => 'xlsx'] + $query)" icon="bi-file-earmark-spreadsheet"
    data-export-link>Excel spreadsheet</x-ui.menu-item>
