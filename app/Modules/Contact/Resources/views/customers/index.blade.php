@extends('layouts.app-back-office')

@section('title', __('Customers'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="customers" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New customer') }}
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    @if ($archived)
        <x-ui.archived-notice />
    @endif

    <x-ui.card flush :pagination="$customers" data-bulk>
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.customers.index')">
                <x-ui.search :placeholder="__('Search by name, phone or email...')" />
                <x-ui.status-filter />
            </x-ui.filters>
        </x-slot:toolbar>

        <x-ui.bulk-bar export="customers" :archive="route('admin.customers.archive')" :restore="route('admin.customers.restore')" />

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
                    @forelse ($customers as $customer)
                        <tr>
                            <x-ui.bulk-check :value="$customer->id" :label="$customer->name" />
                            <td><a href="{{ route('admin.customers.show', $customer->id) }}" class="link-plain">{{ $customer->name }}</a></td>
                            <td class="text-nowrap">{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td class="text-muted">{{ $customer->address ?: '—' }}</td>
                            <td class="text-end">
                                <x-ui.actions-menu row>
                                    <x-ui.menu-item :href="route('admin.customers.show', $customer->id)" icon="bi-eye">{{ __('View') }}</x-ui.menu-item>
                                    <x-ui.menu-item :href="route('admin.customers.edit', $customer->id)" icon="bi-pencil">{{ __('Edit') }}</x-ui.menu-item>
                                    @if ($archived)
                                        <x-ui.menu-item :action="route('admin.customers.restore')" :ids="[$customer->id]"
                                            icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                        <li><hr class="dropdown-divider"></li>
                                        <x-ui.menu-item :action="route('admin.customers.destroy', $customer->id)" method="DELETE"
                                            icon="bi-trash3" tone="danger" :confirm="__('Delete this customer permanently?')"
                                            :confirm-text="__('Only possible if they have no sale: otherwise they stay archived.')">{{ __('Delete permanently') }}</x-ui.menu-item>
                                    @else
                                        <x-ui.menu-item :action="route('admin.customers.archive')" :ids="[$customer->id]" icon="bi-archive"
                                            :confirm="__('Archive this customer?')">{{ __('Archive') }}</x-ui.menu-item>
                                    @endif
                                </x-ui.actions-menu>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="6" icon="bi-person-vcard">
                            {{ $archived ? __('No archived customer.') : __('No customer found.') }}
                        </x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
