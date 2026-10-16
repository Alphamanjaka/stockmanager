{{-- Récapitulatif des erreurs de validation, pour les formulaires dont certains champs n'affichent pas leur erreur. --}}
@if ($errors->any())
    <x-ui.alert tone="danger" title="Please correct the following:" class="mb-3">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
