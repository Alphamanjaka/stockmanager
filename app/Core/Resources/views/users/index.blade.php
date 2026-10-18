@extends('layouts.app-back-office')

@section('title', 'Users')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="users" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New user
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    @if ($archived)
        <x-ui.archived-notice>
            You are viewing <strong>archived</strong> accounts: they can no longer sign in. Their sales and stock counts keep their name.
        </x-ui.archived-notice>
    @endif

    <x-ui.card flush :pagination="$users">
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.users.index')">
                <x-ui.search placeholder="Search by name or email..." />
                <select name="role" class="form-select ui-filter" aria-label="Role">
                    <option value="">All roles</option>
                    <option value="back_office" @selected(request('role') === 'back_office')>Administrators</option>
                    <option value="front_office" @selected(request('role') === 'front_office')>Sellers</option>
                </select>
                <x-ui.status-filter />
            </x-ui.filters>
        </x-slot:toolbar>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Sales made</th>
                        <th>Created on</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="ui-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span class="fw-semibold">{{ $user->name }}</span>
                                </span>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->isBackOffice())
                                    <x-ui.badge tone="primary" icon="bi-shield-check">Administrator</x-ui.badge>
                                @else
                                    <x-ui.badge tone="neutral" icon="bi-shop">Seller</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">{{ $user->sales_count }}</td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <x-ui.actions-menu row>
                                    <x-ui.menu-item :href="route('admin.users.edit', $user)" icon="bi-pencil">Edit</x-ui.menu-item>
                                    @if ($archived)
                                        <x-ui.menu-item :action="route('admin.users.restore')" :ids="[$user->id]"
                                            icon="bi-arrow-counterclockwise">Restore</x-ui.menu-item>
                                        {{-- Un compte qui a vendu ou compté du stock reste archivé : son nom reste sur l'historique --}}
                                        <li><hr class="dropdown-divider"></li>
                                        <x-ui.menu-item :action="route('admin.users.destroy', $user)" method="DELETE" icon="bi-trash3"
                                            tone="danger" :disabled="$user->sales_count > 0"
                                            :title="$user->sales_count > 0 ? 'Has sales: the account stays archived' : null"
                                            confirm="Delete this account permanently?">Delete permanently</x-ui.menu-item>
                                    @else
                                        <x-ui.menu-item :action="route('admin.users.archive')" :ids="[$user->id]" icon="bi-archive"
                                            :disabled="auth()->id() === $user->id"
                                            :title="auth()->id() === $user->id ? 'You cannot archive your own account' : null"
                                            confirm="Archive this account?"
                                            confirm-text="The user will be signed out and will no longer be able to sign in.">
                                            Archive (disable sign-in)
                                        </x-ui.menu-item>
                                    @endif
                                </x-ui.actions-menu>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="6" icon="bi-people">
                            {{ $archived ? 'No archived account.' : 'No user found.' }}
                        </x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
