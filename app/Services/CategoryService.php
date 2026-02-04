<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

class CategoryService
{

    public function create($data)
    {
        // Logic to create a category
        $category = Category::create($data);
        return $category;
    }
    public function getAllCategory($filters = [])
    {
        // On charge le parent et le nombre de produits associés pour éviter les requêtes N+1
        $query = Category::with('parent')->withCount('products');

        // 2. On applique les filtres UNIQUEMENT s'ils sont présents
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        // 3. Retourne soit une collection, soit une pagination
        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();    }

    public function getCategoryById($id)
    {
        return Category::with('parent')->withCount('products')->findOrFail($id);
    }

    public function getProductsByCategory($categoryId, $perPage = 10)
    {
        return Product::where('category_id', $categoryId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getCategoryStats()
    {
        return [
            'total_categories' => Category::count(),
            'most_populated' => Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->first(),
            'total_products_linked' => Product::whereNotNull('category_id')->count(),
        ];
    }

    public function update($id, $data)
    {
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }
    public function delete($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return true;
    }
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has child categories
        if ($category->children()->count() > 0) {
            throw new \Exception("Cannot delete category with child categories.");
        }

        $category->delete();
        return true;
    }
    public function applyfilters($query, $filters)
    {
        $sortableColumns = ['name', 'parent_id', 'created_at'];
        $sort = in_array($filters['sort'] ?? '', $sortableColumns) ? $filters['sort'] : 'name';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->when(isset($filters['sort']) && isset($filters['order']), function ($q) use ($sort, $order) {
            $q->orderBy($sort, $order);
        })->when(isset($filters['parent_id']), function ($q) use ($filters) {
            $q->where('parent_id', $filters['parent_id']);
        });

        return $query->orderBy($sort, $order);
    }
}
