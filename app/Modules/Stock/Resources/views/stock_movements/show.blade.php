@extends('layouts.app-back-office')

@section('title', 'Stock movement #' . $stockMovement->id)
@section('subtitle', $stockMovement->created_at->format('d/m/Y H:i'))

@php
    $incoming = $stockMovement->type === 'in';
    [$tone, $kind] = $stockMovement->kind();
@endphp

@section('content')
    <div class="ui-form-page">
        <x-ui.card title="Movement details">
            <dl class="ui-dl">
                <dt>Product</dt>
                <dd>
                    <a href="{{ route('admin.products.show', $stockMovement->productColor->product->id) }}">
                        {{ $stockMovement->productColor->toString() }}
                    </a>
                </dd>
                <dt>Type</dt>
                <dd><x-ui.badge :tone="$incoming ? 'success' : 'danger'" dot>{{ $incoming ? 'Stock in' : 'Stock out' }}</x-ui.badge></dd>
                <dt>Quantity</dt>
                <dd @class(['fw-semibold', 'text-success' => $incoming, 'text-danger' => !$incoming])>
                    {{ $incoming ? '+' : '-' }}{{ abs($stockMovement->quantity) }}
                </dd>
                <dt>Stock before</dt>
                <dd>{{ $stockMovement->stock_before }}</dd>
                <dt>Stock after</dt>
                <dd class="fw-semibold">{{ $stockMovement->stock_after }}</dd>
                <dt>Reason</dt>
                <dd>{{ $stockMovement->reason ?? '—' }}</dd>
                <dt>Source</dt>
                <dd>
                    <x-ui.badge :tone="$tone">{{ $kind }}</x-ui.badge>
                    @if ($stockMovement->sourceUrl())
                        <a href="{{ $stockMovement->sourceUrl() }}" class="ms-1">Open the document</a>
                    @endif
                </dd>
                <dt>By</dt>
                <dd>{{ $stockMovement->user->name ?? '—' }}</dd>
                <dt>Date</dt>
                <dd>{{ $stockMovement->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </x-ui.card>
    </div>
@endsection
