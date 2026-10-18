@extends('layouts.app-back-office')

@section('title', $customer->name)
@section('subtitle', 'Customer since ' . $customer->created_at->format('d/m/Y'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.menu-item :href="route('admin.sales.index', ['customer' => $customer->id])" icon="bi-receipt">Sales history</x-ui.menu-item>
        <li><hr class="dropdown-divider"></li>
        @if ($customer->isArchived())
            {{-- Possible seulement sans vente : sinon la base refuse et le client reste archivé --}}
            <x-ui.menu-item :action="route('admin.customers.destroy', $customer->id)" method="DELETE" icon="bi-trash3" tone="danger"
                confirm="Delete this customer permanently?" confirm-text="Only possible if they have no sale: otherwise they stay archived.">
                Delete permanently
            </x-ui.menu-item>
        @else
            <x-ui.menu-item :action="route('admin.customers.archive')" :ids="[$customer->id]" icon="bi-archive"
                confirm="Archive this customer?" confirm-text="They will no longer be offered at the checkout. Their sales keep their name.">
                Archive
            </x-ui.menu-item>
        @endif
    </x-ui.actions-menu>
    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </a>
@endsection

@section('content')
    @if ($customer->isArchived())
        <x-ui.archived-notice :restore="route('admin.customers.restore')" :id="$customer->id" :date="$customer->archived_at">
            This customer is <strong>archived</strong>: they are no longer offered at the checkout.
        </x-ui.archived-notice>
    @endif

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
@endsection
