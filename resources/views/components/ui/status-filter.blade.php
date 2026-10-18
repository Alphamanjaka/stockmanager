{{--
    Filtre « Active / Archived » d'une liste d'éléments archivables, à placer dans <x-ui.filters>.
    Envoie status=archived pour afficher les archives (scope withStatus() du trait Archivable).
--}}
<select name="status" {{ $attributes->class('form-select ui-filter') }} aria-label="Status">
    <option value="">Active</option>
    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
</select>
