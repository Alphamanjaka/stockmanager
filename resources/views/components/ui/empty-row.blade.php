{{-- Ligne « aucun résultat » d'un tableau : <x-ui.empty-row :colspan="5" icon="bi-tags">No brand yet.</x-ui.empty-row> --}}
@props(['colspan', 'icon' => 'bi-inbox', 'title' => null])

<tr class="ui-empty-row">
    <td colspan="{{ $colspan }}">
        <x-ui.empty :icon="$icon" :title="$title">{{ $slot }}</x-ui.empty>
    </td>
</tr>
