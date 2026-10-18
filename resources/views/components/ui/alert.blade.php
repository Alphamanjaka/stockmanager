{{-- Alerte avec icône. tone : info, success, warning, danger, secondary. --}}
@props(['tone' => 'info', 'icon' => null, 'title' => null])

@php
    $icon ??= [
        'success' => 'bi-check-circle',
        'warning' => 'bi-exclamation-triangle',
        'danger' => 'bi-exclamation-octagon',
    ][$tone] ?? 'bi-info-circle';
@endphp

<div role="alert" {{ $attributes->class(["alert alert-{$tone} ui-alert"]) }}>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <div class="ui-alert-body">
        @if ($title)
            <div class="alert-heading">{{ $title }}</div>
        @endif
        {{ $slot }}
    </div>
</div>
