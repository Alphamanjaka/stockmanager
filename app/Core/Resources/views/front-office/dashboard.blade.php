@extends('layouts.app-front-office')

@section('title', 'Welcome, ' . $user->name)

@section('actions')
    <a href="{{ route('saler.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New sale
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Sales today" :value="money($salesToday)" icon="bi-calendar-day" tone="success" />
            <x-ui.stat label="Sales this month" :value="money($salesThisMonth)" icon="bi-calendar-month" tone="primary" />
            <x-ui.stat label="Number of sales" :value="$totalSales" icon="bi-receipt" tone="info" />
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('saler.create') }}" class="ui-stat h-100">
                    <span class="ui-stat-icon ui-tone-primary"><i class="bi bi-shop" aria-hidden="true"></i></span>
                    <span class="ui-stat-body">
                        <span class="d-block fw-semibold">Open the point of sale</span>
                        <span class="ui-stat-hint d-block">Scan or search products and take the payment.</span>
                    </span>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('saler.index') }}" class="ui-stat h-100">
                    <span class="ui-stat-icon ui-tone-neutral"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                    <span class="ui-stat-body">
                        <span class="d-block fw-semibold">Sales history</span>
                        <span class="ui-stat-hint d-block">Find a sale, print a receipt or an invoice.</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
@endsection
