@extends('layouts.app-back-office')

@section('title', __('Users'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="users" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New user') }}
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    @if ($archived)
        <x-ui.archived-notice>
            {!! __('You are viewing <strong>archived</strong> accounts: they can no longer sign in. Their sales and stock counts keep their name.') !!}
        </x-ui.archived-notice>
    @endif

    <x-ui.card flush :pagination="$users" data-bulk>
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.users.index')">
                <x-ui.search :placeholder="__('Search by name or email...')" />
                <select name="role" class="form-select ui-filter" aria-label="{{ __('Role') }}">
                    <option value="">{{ __('All roles') }}</option>
                    @foreach (\App\Core\Support\Role::ALL as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ \App\Core\Support\Role::label($role) }}</option>
                    @endforeach
                </select>
                <x-ui.status-filter />
            </x-ui.filters>
        </x-slot:toolbar>

        <x-ui.bulk-bar export="users" :archive="route('admin.users.archive')" :restore="route('admin.users.restore')" />

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <x-ui.bulk-check-all />
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th class="text-end">{{ __('Sales made') }}</th>
                        <th>{{ __('Created on') }}</th>
                        <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <x-ui.bulk-check :value="$user->id" :label="$user->name" />
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="ui-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span class="fw-semibold">{{ $user->name }}</span>
                                </span>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    [$tone, $icon] = match ($user->role) {
                                        \App\Core\Support\Role::ADMIN => ['primary', 'bi-shield-check'],
                                        \App\Core\Support\Role::MANAGER => ['info', 'bi-briefcase'],
                                        \App\Core\Support\Role::STOREKEEPER => ['warning', 'bi-box-seam'],
                                        default => ['neutral', 'bi-shop'],
                                    };
                                @endphp
                                <x-ui.badge :tone="$tone" :icon="$icon">{{ \App\Core\Support\Role::label($user->role) }}</x-ui.badge>
                            </td>
                            <td class="text-end">{{ $user->sales_count }}</td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <x-ui.actions-menu row>
                                    <x-ui.menu-item :href="route('admin.users.edit', $user)" icon="bi-pencil">{{ __('Edit') }}</x-ui.menu-item>
                                    @if ($archived)
                                        <x-ui.menu-item :action="route('admin.users.restore')" :ids="[$user->id]"
                                            icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                        {{-- Un compte qui a vendu ou compté du stock reste archivé : son nom reste sur l'historique --}}
                                        <li><hr class="dropdown-divider"></li>
                                        <x-ui.menu-item :action="route('admin.users.destroy', $user)" method="DELETE" icon="bi-trash3"
                                            tone="danger" :disabled="$user->sales_count > 0"
                                            :title="$user->sales_count > 0 ? __('Has sales: the account stays archived') : null"
                                            :confirm="__('Delete this account permanently?')">{{ __('Delete permanently') }}</x-ui.menu-item>
                                    @else
                                        <x-ui.menu-item :action="route('admin.users.archive')" :ids="[$user->id]" icon="bi-archive"
                                            :disabled="auth()->id() === $user->id"
                                            :title="auth()->id() === $user->id ? __('You cannot archive your own account') : null"
                                            :confirm="__('Archive this account?')"
                                            :confirm-text="__('The user will be signed out and will no longer be able to sign in.')">
                                            {{ __('Archive (disable sign-in)') }}
                                        </x-ui.menu-item>
                                    @endif
                                </x-ui.actions-menu>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="7" icon="bi-people">
                            {{ $archived ? __('No archived account.') : __('No user found.') }}
                        </x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
