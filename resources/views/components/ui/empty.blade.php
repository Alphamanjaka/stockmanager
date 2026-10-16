{{-- État vide (liste sans résultat, bloc sans données) : icône, titre facultatif, message (slot). --}}
@props(['icon' => 'bi-inbox', 'title' => null])

<div {{ $attributes->class('ui-empty') }}>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @if ($title)
        <div class="ui-empty-title">{{ $title }}</div>
    @endif
    @if ($slot->isNotEmpty())
        <div>{{ $slot }}</div>
    @endif
</div>
