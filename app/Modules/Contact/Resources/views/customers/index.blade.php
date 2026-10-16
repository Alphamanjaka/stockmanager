@extends('layouts.app-back-office')

@section('title', 'Customers')

@section('actions')
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New customer
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$customers">
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.customers.index')">
                <x-ui.search placeholder="Search by name, phone or email..." />
            </x-ui.filters>
        </x-slot:toolbar>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><a href="{{ route('admin.customers.show', $customer->id) }}" class="link-plain">{{ $customer->name }}</a></td>
                            <td class="text-nowrap">{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td class="text-muted">{{ $customer->address ?: '—' }}</td>
                            <td class="text-end">
                                <div class="ui-row-actions">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="5" icon="bi-person-vcard">No customer found.</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
