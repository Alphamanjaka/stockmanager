<?php

namespace App\Core\Exports;

use App\Core\Services\UserService;
use App\Core\Support\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersExport extends ListExport
{
    public function __construct(private UserService $users) {}

    public function title(): string
    {
        return __('Users');
    }

    public function columns(): array
    {
        return [
            Column::make(__('Name'), 'name'),
            Column::make(__('Email'), 'email'),
            Column::make(__('Role'), fn (User $user) => Role::label($user->role))->width('14%'),
            Column::make(__('Sales made'), 'sales_count')->number()->width('12%'),
            Column::make(__('Created on'), 'created_at')->date()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->users->listQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        return [__('Role') => in_array($filters['role'] ?? null, Role::ALL, true) ? Role::label($filters['role']) : null];
    }
}
