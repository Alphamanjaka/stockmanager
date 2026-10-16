{{--
    Indicateur chiffré (tableaux de bord, en-têtes de liste). À regrouper dans <div class="ui-stats">.

        <x-ui.stat label="Total purchases" :value="number_format($total, 2)" unit="MGA" icon="bi-cart-check" tone="primary" />

    tone : primary, success, warning, danger, info, neutral ; href rend la tuile cliquable ;
    hint (ou le slot) : ligne de précision sous la valeur.
--}}
@props(['label', 'value', 'unit' => null, 'icon' => null, 'tone' => 'primary', 'hint' => null, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['ui-stat', "ui-tone-{$tone}"]) }}>
    @if ($icon)
        <span class="ui-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
    @endif
    <div class="ui-stat-body">
        <div class="ui-stat-label">{{ $label }}</div>
        <div class="ui-stat-value" title="{{ $value }}{{ $unit ? ' ' . $unit : '' }}">
            {{ $value }}
            @if ($unit)
                <small>{{ $unit }}</small>
            @endif
        </div>
        @if ($hint)
            <div class="ui-stat-hint">{{ $hint }}</div>
        @elseif ($slot->isNotEmpty())
            <div class="ui-stat-hint">{{ $slot }}</div>
        @endif
    </div>
</{{ $tag }}>
