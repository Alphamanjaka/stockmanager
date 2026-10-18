{{--
    Action de la barre de sélection (<x-ui.bulk-bar>) : formulaire envoyé avec les ids[] cochés
    (ajoutés par resources/js/bulk-select.js). « :count » dans confirm est remplacé par le nombre d'éléments.
--}}
@props(['action', 'icon' => null, 'confirm' => null, 'confirmText' => null, 'tone' => null])

<form action="{{ $action }}" method="POST" class="m-0" data-bulk-form>
    @csrf
    <button type="submit" @class(['btn btn-sm', 'btn-outline-danger' => $tone === 'danger', 'btn-outline-secondary' => $tone !== 'danger'])
        @if ($confirm) data-bulk-confirm="{{ $confirm }}" data-confirm="{{ $confirm }}" data-confirm-ok="{{ trim(strip_tags($slot)) }}" @endif
        @if ($confirmText) data-confirm-text="{{ $confirmText }}" @endif
        @if ($tone === 'danger') data-confirm-tone="danger" @endif>
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </button>
</form>
