<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get all products with filtering, sorting and pagination
     */
    public function getAllProducts($filters = [])
    {
        // 1. On commence toujours par une base de requête (Query Builder)
        $query = Product::with('category');

        // 2. On applique les filtres UNIQUEMENT s'ils sont présents
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        // 3. Retourne soit une collection, soit une pagination
        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    /**
     * Get all categories (for dropdown)
     */
    public function getAllCategories()
    {
        return Category::all();
    }

    /**
     * Get products with available stock
     */
    public function getAvailableProducts()
    {
        return Product::where('quantity_stock', '>', 0)->get();
    }

    /**
     * Get single product by ID
     */
    public function getProductById($id)
    {
        return Product::with('category')->findOrFail($id);
    }

    /**
     * Create a new product
     */
    public function createProduct($data)
    {
        return Product::create($data);
    }

    /**
     * Update product
     */
    public function updateProduct($id, $data)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    /**
     * Delete product
     */
    public function deleteProduct($id)
    {
        // On vérifie que le produit n'est lié à aucune ligne de vente ou d'achat
        $product = Product::findOrFail($id);

        $hasSales = SaleItem::where('product_id', $id)->exists();
        $hasPurchases = DB::table('purchase_items')->where('product_id', $id)->exists();

        if ($hasSales || $hasPurchases) {
            throw new \Exception("Impossible de supprimer ce produit car il est lié à des ventes ou des achats existants.");
        }

        $product->delete();
        return $product;
    }

    /**
     * Get products with low stock
     */
    public function getLowStockProducts()
    {
        return Product::whereRaw('quantity_stock < alert_stock')->get();
    }

    /**
     * Check if product has available stock
     */
    public function hasAvailableStock($productId, $quantity = 1)
    {
        $product = Product::findOrFail($productId);
        return $product->quantity_stock >= $quantity;
    }

    // filter method for products list
    protected function applyFilters($query, $filters)
    {
        // On réutilise ta logique de colonnes autorisées
        $sortableColumns = ['name', 'price', 'quantity_stock', 'created_at'];

        $sort = in_array($filters['sort'] ?? '', $sortableColumns) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%");
        })
            ->when($filters['category'] ?? null, function ($q, $category) {
                $q->where('category_id', $category);
            });

        return $query
            ->orderBy($sort, $order);
    }

    /**
     * Get the most sold product.
     */
    public function getMostSoldProduct()
    {
        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->with('product:id,name') // Eager load only what's needed
            ->orderByDesc('total_sold')
            ->first();
    }

    /**
     * Get the least sold product among those that have been sold.
     */
    public function getLeastSoldProduct()
    {
        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->with('product:id,name')
            ->orderBy('total_sold', 'asc')
            ->first();
    }
}
