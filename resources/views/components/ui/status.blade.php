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
        'partiallyreceived' => 'warning',
        'received' => 'success',
        'validated' => 'success',
        'paid' => 'success',
        'cancelled' => 'danger',
        'canceled' => 'danger',
    ][strtolower((string) $value)] ?? 'neutral';

    // Libellés traduits : les statuts sont enregistrés en anglais
    $labels = [
        'draft' => __('Draft'),
        'pending' => __('Pending'),
        'ordered' => __('Ordered'),
        'partiallyreceived' => __('Partially received'),
        'received' => __('Received'),
        'validated' => __('Validated'),
        'paid' => __('Paid'),
        'cancelled' => __('Cancelled'),
        'canceled' => __('Cancelled'),
    ];
@endphp

<x-ui.badge :tone="$tone" dot {{ $attributes }}>{{ $label ?? $labels[strtolower((string) $value)] ?? ucfirst((string) $value) }}</x-ui.badge>
