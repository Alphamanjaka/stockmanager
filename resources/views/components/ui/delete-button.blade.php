{{--
    Suppression avec confirmation (resources/js/ui.js).
    Sans label : bouton icône d'une ligne de tableau ; avec label : bouton texte rouge.
        <x-ui.delete-button :action="route('admin.brands.destroy', $brand)" confirm="Delete this brand?" />
--}}
@props(['action', 'confirm', 'label' => null, 'title' => null, 'disabled' => false])

@php $title ??= __('Delete'); @endphp

<form action="{{ $action }}" method="POST" class="d-inline-flex m-0">
    @csrf
    @method('DELETE')
    @if ($label)
        <button type="submit" {{ $attributes->class('btn btn-outline-danger') }} @disabled($disabled)
            data-confirm="{{ $confirm }}" data-confirm-tone="danger" data-confirm-ok="{{ $label }}">
            <i class="bi bi-trash3" aria-hidden="true"></i> {{ $label }}
        </button>
    @else
        <button type="submit" {{ $attributes->class('btn btn-icon btn-icon-danger') }} @disabled($disabled)
            title="{{ $title }}" aria-label="{{ $title }}"
            data-confirm="{{ $confirm }}" data-confirm-tone="danger" data-confirm-ok="{{ $title }}">
            <i class="bi bi-trash3" aria-hidden="true"></i>
        </button>
    @endif
</form>
