<?php

namespace App\Modules\Contact\Services;

use App\Modules\Contact\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function getAllSuppliers(array $filters = [])
    {
        // Initial query : fournisseurs en service, ou les archives (status=archived)
        $query = Supplier::query()->withStatus($filters['status'] ?? null);
        // Apply filters if any
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }
        // Return paginated results
        return $query->paginate($filters['per_page'] ?? 15)->appends(request()->except('page'));
    }

    /**
     * Fournisseurs proposés dans les formulaires : tous les fournisseurs en service (sans pagination),
     * plus $keepId (fournisseur actuel d'un achat modifié, même archivé).
     */
    public function getActiveSuppliers(?int $keepId = null)
    {
        return Supplier::where(fn ($query) => $query->active()->when($keepId, fn ($q) => $q->orWhere('id', $keepId)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function createSupplier(array $data): Supplier
    {
        if (isset($data['name'])) {
            $data['name'] = mb_strtolower(trim($data['name']));
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }
        return Supplier::create($data);
    }

    public function findByEmail(string $email): ?Supplier
    {
        return Supplier::where('email', mb_strtolower(trim($email)))->first();
    }

    public function getSuppliersByEmails(array $emails)
    {
        return Supplier::whereIn('email', $emails)->pluck('id', 'email');
    }

    public function  applyFilters($query, array $filters)
    {
        // On réutilise ta logique de colonnes autorisées
        $sortableColumns = ['name', 'email', 'created_at'];

        $sort = in_array($filters['sort'] ?? '', $sortableColumns) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->when($filters['search'] ?? null, fn ($q, $search) => $q->search($search))
            ->orderBy($sort, $order);
    }
    public function getSupplierById(int $id)
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
        $purchases = $supplier->purchases()->where('state', 'Received');
        // Statistiques globales pour ce fournisseur
        $totalSpent = $purchases->sum('total_net');
        $lastPurchase = $purchases->latest()->first();

        // Top 5 des produits achetés chez ce fournisseur
        // purchase_items référence une variante (product_colors), pas directement un produit
        $topProducts = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('product_colors', 'purchase_items.product_color_id', '=', 'product_colors.id')
            ->join('products', 'product_colors.product_id', '=', 'products.id')
            ->where('purchases.supplier_id', $supplier->id)
            ->where('purchases.state', 'Received') // Only consider received purchases for top products
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
        if (isset($data['name'])) {
            $data['name'] = mb_strtolower(trim($data['name']));
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }
        $supplier->update($data);
        return $supplier;
    }
}
