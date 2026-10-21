@extends('layouts.app-back-office')

@section('title', __('Suppliers'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="suppliers" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">{{ __('Import suppliers') }}</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New supplier') }}
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    @if ($archived)
        <x-ui.archived-notice />
    @endif

    <x-ui.card flush :pagination="$suppliers" data-bulk>
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.suppliers.index')">
                <x-ui.search :placeholder="__('Search by name, phone, email or notes...')" />
                <x-ui.status-filter />
            </x-ui.filters>
        </x-slot:toolbar>

        <x-ui.bulk-bar export="suppliers" :archive="route('admin.suppliers.archive')" :restore="route('admin.suppliers.restore')" />

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <x-ui.bulk-check-all />
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <x-ui.bulk-check :value="$supplier->id" :label="$supplier->name" />
                            <td><a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="link-plain">{{ $supplier->name }}</a></td>
                            <td class="text-nowrap">{{ $supplier->phone ?: '—' }}</td>
                            <td>{{ $supplier->email ?: '—' }}</td>
                            <td class="text-muted">{{ $supplier->address ?: '—' }}</td>
                            <td class="text-end">
                                <x-ui.actions-menu row>
                                    <x-ui.menu-item :href="route('admin.suppliers.show', $supplier->id)" icon="bi-eye">{{ __('View') }}</x-ui.menu-item>
                                    <x-ui.menu-item :href="route('admin.suppliers.edit', $supplier->id)" icon="bi-pencil">{{ __('Edit') }}</x-ui.menu-item>
                                    @if ($archived)
                                        <x-ui.menu-item :action="route('admin.suppliers.restore')" :ids="[$supplier->id]"
                                            icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                        <li><hr class="dropdown-divider"></li>
                                        <x-ui.menu-item :action="route('admin.suppliers.destroy', $supplier->id)" method="DELETE"
                                            icon="bi-trash3" tone="danger" :confirm="__('Delete this supplier permanently?')"
                                            :confirm-text="__('Only possible if it has no purchase: otherwise it stays archived.')">{{ __('Delete permanently') }}</x-ui.menu-item>
                                    @else
                                        <x-ui.menu-item :action="route('admin.suppliers.archive')" :ids="[$supplier->id]" icon="bi-archive"
                                            :confirm="__('Archive this supplier?')">{{ __('Archive') }}</x-ui.menu-item>
                                    @endif
                                </x-ui.actions-menu>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="6" icon="bi-truck">
                            {{ $archived ? __('No archived supplier.') : __('No supplier found.') }}
                        </x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
