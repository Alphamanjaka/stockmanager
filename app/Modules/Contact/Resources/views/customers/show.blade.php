@extends('layouts.app-back-office')

@section('title', $customer->name)
@section('subtitle', 'Customer since ' . $customer->created_at->format('d/m/Y'))

@section('actions')
    <a href="{{ route('admin.sales.index', ['customer' => $customer->id]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-receipt" aria-hidden="true"></i> Sales history
    </a>
    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card title="Contact information">
                <dl class="ui-dl">
                    <dt>Phone</dt>
                    <dd>{{ $customer->phone ?: '—' }}</dd>
                    <dt>Email</dt>
                    <dd>
                        @if ($customer->email)
                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt>Address</dt>
                    <dd>{{ $customer->address ?: '—' }}</dd>
                    <dt>Notes</dt>
                    <dd class="text-prewrap">{{ $customer->notes ?: '—' }}</dd>
                </dl>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card title="Delete customer" tone="danger">
                <p class="text-muted mb-3">Deleting a customer keeps their sales (they become anonymous).</p>
                <x-ui.delete-button :action="route('admin.customers.destroy', $customer->id)" confirm="Delete this customer?"
                    label="Delete" class="w-100" />
            </x-ui.card>
        </div>
    </div>
@endsection
