@extends('layouts.app-front-office')

@section('title', __('Welcome, :name', ['name' => $user->name]))

@section('actions')
    <a href="{{ route('saler.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New sale') }}
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat :label="__('Sales today')" :value="money($salesToday)" icon="bi-calendar-day" tone="success" />
            <x-ui.stat :label="__('Sales this month')" :value="money($salesThisMonth)" icon="bi-calendar-month" tone="primary" />
            <x-ui.stat :label="__('Number of sales')" :value="$totalSales" icon="bi-receipt" tone="info" />
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('saler.create') }}" class="ui-stat h-100">
                    <span class="ui-stat-icon ui-tone-primary"><i class="bi bi-shop" aria-hidden="true"></i></span>
                    <span class="ui-stat-body">
                        <span class="d-block fw-semibold">{{ __('Open the point of sale') }}</span>
                        <span class="ui-stat-hint d-block">{{ __('Scan or search products and take the payment.') }}</span>
                    </span>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('saler.index') }}" class="ui-stat h-100">
                    <span class="ui-stat-icon ui-tone-neutral"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                    <span class="ui-stat-body">
                        <span class="d-block fw-semibold">{{ __('Sales history') }}</span>
                        <span class="ui-stat-hint d-block">{{ __('Find a sale, print a receipt or an invoice.') }}</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
@endsection
