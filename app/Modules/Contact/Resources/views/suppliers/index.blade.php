@extends('layouts.app-back-office')

@section('title', 'Suppliers')

@section('actions')
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New supplier
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$suppliers">
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.suppliers.index')">
                <x-ui.search placeholder="Search by name, phone, email or notes..." />
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
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td><a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="link-plain">{{ $supplier->name }}</a></td>
                            <td class="text-nowrap">{{ $supplier->phone ?: '—' }}</td>
                            <td>{{ $supplier->email ?: '—' }}</td>
                            <td class="text-muted">{{ $supplier->address ?: '—' }}</td>
                            <td class="text-end">
                                <div class="ui-row-actions">
                                    <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="5" icon="bi-truck">No supplier found.</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
