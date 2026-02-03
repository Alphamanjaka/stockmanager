<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function getAllSuppliers(array $filters = [])
    {
        // Initial query
        $query = Supplier::query();
        // Apply filters if any
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }
        // Return paginated results
        return $query->paginate($filters['per_page'] ?? 15)->appends(request()->except('page'));
    }

    public function createSupplier($data)
    {
        return Supplier::create($data);
    }
    public function  applyFilters($query, $filters)
    {
        // On réutilise ta logique de colonnes autorisées
        $sortableColumns = ['name', 'email', 'created_at'];

        $sort = in_array($filters['sort'] ?? '', $sortableColumns) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
            ->orderBy($sort, $order);
    }
}
