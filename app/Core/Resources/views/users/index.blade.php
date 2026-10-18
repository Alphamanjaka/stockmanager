@extends('layouts.app-back-office')

@section('title', 'Users')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New user
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$users">
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.users.index')">
                <x-ui.search placeholder="Search by name or email..." />
                <select name="role" class="form-select ui-filter" aria-label="Role">
                    <option value="">All roles</option>
                    <option value="back_office" @selected(request('role') === 'back_office')>Administrators</option>
                    <option value="front_office" @selected(request('role') === 'front_office')>Sellers</option>
                </select>
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
                                <div class="ui-row-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <x-ui.delete-button :action="route('admin.users.destroy', $user)" confirm="Delete this user?"
                                        :disabled="auth()->id() === $user->id"
                                        :title="auth()->id() === $user->id ? 'You cannot delete your own account' : 'Delete'" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="6" icon="bi-people">No user found.</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
