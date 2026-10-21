@extends('layouts.app-front-office')

@section('title', __('Sale :reference', ['reference' => $sale->reference]))

@section('actions')
    <a href="{{ route('saler.receipt', ['sale' => $sale->id, 'print' => 1]) }}" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-printer" aria-hidden="true"></i> {{ __('pos.print_receipt') }}
    </a>
    <a href="{{ route('saler.pdf', $sale->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
@endsection

@section('content')
    <div class="ui-form-page">
        {{-- Retours enregistrés par le back office : montants seulement, jamais les coûts --}}
        @if ($sale->returnStatus())
            <x-ui.alert tone="warning" icon="bi-arrow-counterclockwise" class="mb-3">
                {{ $sale->returnStatus() === 'returned' ? __('This sale was cancelled:') : __('Some items of this sale were returned:') }}
                {{ __(':amount refunded', ['amount' => money($sale->refundedTotal(), 2, $sale->currency_symbol)]) }}
                ({{ $sale->returns->pluck('reference')->join(', ') }}).
                {{ __('Net after returns: :amount.', ['amount' => money($sale->netAfterReturns(), 2, $sale->currency_symbol)]) }}
            </x-ui.alert>
        @endif
        @include('sale::partials.invoice')
    </div>
@endsection
