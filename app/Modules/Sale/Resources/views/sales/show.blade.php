@extends('layouts.app-front-office')

@section('title', 'Sale ' . $sale->reference)

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
        @include('sale::partials.invoice')
    </div>
@endsection
