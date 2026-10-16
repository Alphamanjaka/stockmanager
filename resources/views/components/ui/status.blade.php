{{--
    Statut d'un document (achat, inventaire...) : couleur selon l'étape.
        <x-ui.status :value="$purchase->state" />   → Draft (gris), Ordered (bleu), Received (vert)
--}}
@props(['value', 'label' => null])

@php
    $tone = [
        'draft' => 'neutral',
        'pending' => 'warning',
        'ordered' => 'info',
        'received' => 'success',
        'validated' => 'success',
        'paid' => 'success',
        'cancelled' => 'danger',
        'canceled' => 'danger',
    ][strtolower((string) $value)] ?? 'neutral';
@endphp

<x-ui.badge :tone="$tone" dot {{ $attributes }}>{{ $label ?? ucfirst((string) $value) }}</x-ui.badge>
