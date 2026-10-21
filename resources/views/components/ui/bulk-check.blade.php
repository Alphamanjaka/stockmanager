{{--
    Case de sélection d'une ligne (voir <x-ui.bulk-bar>). Toute la cellule est cliquable ;
    Maj + clic coche ou décoche toutes les lignes entre deux clics.
--}}
@props(['value', 'label' => null])

<td class="ui-col-check">
    <label class="ui-check-hit">
        <input type="checkbox" class="form-check-input" value="{{ $value }}" data-bulk-item
            aria-label="{{ __('Select :item', ['item' => $label ?? "#{$value}"]) }}">
    </label>
</td>
