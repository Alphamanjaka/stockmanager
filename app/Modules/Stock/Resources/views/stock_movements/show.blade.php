@extends('layouts.app-back-office')

@section('title', __('Stock movement #:id', ['id' => $stockMovement->id]))
@section('subtitle', $stockMovement->created_at->format('d/m/Y H:i'))

@php
    $incoming = $stockMovement->type === 'in';
    [$tone, $kind] = $stockMovement->kind();
@endphp

@section('content')
    <div class="ui-form-page">
        <x-ui.card :title="__('Movement details')">
            <dl class="ui-dl">
                <dt>{{ __('Product') }}</dt>
                <dd>
                    <a href="{{ route('admin.products.show', $stockMovement->productColor->product->id) }}">
                        {{ $stockMovement->productColor->toString() }}
                    </a>
                </dd>
                <dt>{{ __('Type') }}</dt>
                <dd><x-ui.badge :tone="$incoming ? 'success' : 'danger'" dot>{{ $incoming ? __('Stock in') : __('Stock out') }}</x-ui.badge></dd>
                <dt>{{ __('Quantity') }}</dt>
                <dd @class(['fw-semibold', 'text-success' => $incoming, 'text-danger' => !$incoming])>
                    {{ $incoming ? '+' : '-' }}{{ abs($stockMovement->quantity) }}
                </dd>
                <dt>{{ __('Stock before') }}</dt>
                <dd>{{ $stockMovement->stock_before }}</dd>
                <dt>{{ __('Stock after') }}</dt>
                <dd class="fw-semibold">{{ $stockMovement->stock_after }}</dd>
                <dt>{{ __('Reason') }}</dt>
                <dd>{{ $stockMovement->reason ?? '—' }}</dd>
                <dt>{{ __('Source') }}</dt>
                <dd>
                    <x-ui.badge :tone="$tone">{{ $kind }}</x-ui.badge>
                    @if ($stockMovement->sourceUrl())
                        <a href="{{ $stockMovement->sourceUrl() }}" class="ms-1">{{ __('Open the document') }}</a>
                    @endif
                </dd>
                <dt>{{ __('By') }}</dt>
                <dd>{{ $stockMovement->user->name ?? '—' }}</dd>
                <dt>{{ __('Date') }}</dt>
                <dd>{{ $stockMovement->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </x-ui.card>
    </div>
@endsection
