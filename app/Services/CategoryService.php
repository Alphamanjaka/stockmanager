<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function getAvailableChildren($parentId = null)
    {
        return Category::where('parent_id', $parentId)->pluck('name', 'id');
    }

    public function create($data)
    {
        // Logic to create a category
        $category = Category::create($data);
        $this->syncChildren($category->id, $data['children'] ?? []);
        return $category;
    }
    public function getAllCategory($filters = [], $isPaginated = true)
    {
        // On charge le parent et le nombre de produits associés pour éviter les requêtes N+1
        $query = Category::with('parent')->withCount('products')->with('children'); // On charge aussi les enfants pour éviter les N+1 dans la vue de création/édition

        // 2. On applique les filtres UNIQUEMENT s'ils sont présents
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }
        if ($isPaginated) {
            // 3. Retourne une pagination
            return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
        } else {
            // 3. Retourne une collection simple
            return $query->get();
        }
    }

    /**
     * Récupère les catégories sous forme de liste plate (ID => Nom)
     * Idéal pour les selects de formulaires.
     */
    public function getCategoriesList(): \Illuminate\Support\Collection
    {
        return Category::orderBy('name')->pluck('name', 'id');
    }

    public function getCategoryById($id)
    {
        return Category::with('parent')->withCount('products')->with('children')->findOrFail($id);
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
        $category = DB::transaction(function () use ($id, $data) {
            $category = Category::findOrFail($id);
            $category->update($data);
            $this->syncChildren($category->id, $data['children'] ?? []);
            return $category;
        });
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
        $query = $query->where(function ($q) use ($filters) {
            if (isset($filters['search'])) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            }
        });

        return $query->orderBy($sort, $order);
    }

    /**
     * Met à jour les relations parent/enfant.
     */
    protected function syncChildren($parentId, $childrenIds)
    {
        // Sécurité : s'assurer que c'est un tableau et qu'on ne s'ajoute pas soi-même comme enfant
        $childrenIds = is_array($childrenIds) ? $childrenIds : [];
        $childrenIds = array_diff($childrenIds, [$parentId]);

        // 1. Détacher les anciens enfants (ils deviennent orphelins ou racines)
        Category::where('parent_id', $parentId)->update(['parent_id' => null]);
        Log::info("Detached children for category $parentId");
        Log::info("Children IDs: " . implode(', ', $childrenIds));

        // 2. Attacher les nouveaux enfants sélectionnés
        if (!empty($childrenIds)) {
            Log::info("Syncing children for category $parentId: " . implode(', ', $childrenIds));
            Category::whereIn('id', $childrenIds)->update(['parent_id' => $parentId]);
        }
    }
}
