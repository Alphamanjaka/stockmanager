{{--
    Statut : texte coloré sur fond doux. tone : success, warning, danger, info, primary, neutral.
        <x-ui.badge tone="success" dot>Received</x-ui.badge>
--}}
@props(['tone' => 'neutral', 'icon' => null, 'dot' => false])

<span {{ $attributes->class(['ui-badge', "ui-tone-{$tone}", 'ui-badge-dot' => $dot]) }}>
    @if ($icon)
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
