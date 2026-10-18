<?php

namespace App\Core\Exports;

use App\Core\Services\UserService;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersExport extends ListExport
{
    public function __construct(private UserService $users) {}

    public function title(): string
    {
        return 'Users';
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name'),
            Column::make('Email', 'email'),
            Column::make('Role', fn (User $user) => $user->isBackOffice() ? 'Administrator' : 'Seller')->width('14%'),
            Column::make('Sales made', 'sales_count')->number()->width('12%'),
            Column::make('Created on', 'created_at')->date()->width('12%'),
        ];
    }

    protected function query(array $filters): Builder
    {
        return $this->users->listQuery($filters);
    }

    protected function filterLabels(array $filters): array
    {
        return ['Role' => ['back_office' => 'Administrators', 'front_office' => 'Sellers'][$filters['role'] ?? ''] ?? null];
    }
}
