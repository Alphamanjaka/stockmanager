<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

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
    public function getSupplierById($id)
    {
        return Supplier::findOrFail($id);
    }

    /**
     * Récupère les détails et statistiques pour un fournisseur donné.
     *
     * @param Supplier $supplier
     * @return array
     */
    public function getSupplierDetails(Supplier $supplier): array
    {
        $purchases=$supplier->purchases()->where('state', 'Received')->orWhere('state', 'Paid');
        // Statistiques globales pour ce fournisseur
        $totalSpent = $purchases->sum('total_net');
        $lastPurchase = $purchases->latest()->first();

        // Top 5 des produits achetés chez ce fournisseur
        $topProducts = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->where('purchases.supplier_id', $supplier->id)
            ->where('purchases.state', 'Received')
            ->orWhere('purchases.state', 'Paid')
            ->select(
                'products.name',
                DB::raw('SUM(purchase_items.quantity) as total_qty'),
                DB::raw('SUM(purchase_items.subtotal) as total_cost')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return compact('totalSpent', 'lastPurchase', 'topProducts');
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier;
    }

    /**
     * @param Supplier $supplier
     * @return bool
     * @throws \Exception
     */
    public function deleteSupplier(Supplier $supplier): bool
    {
        if ($supplier->purchases()->exists()) {
            throw new \Exception('Impossible de supprimer ce fournisseur : des achats y sont liés.');
        }

        return $supplier->delete();
    }
}