<?php

namespace App\Services;

use App\Models\Category;

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
        $per_page = $filters['per_page'] ?? 15;
        $searchoption = $filters['search'] ?? '';
        $query = Category::query();

        if (!empty($searchoption)) {
            $query->where('name', 'like', "%{$searchoption}%");
        }
        if (!empty($query) && isset($filters['sort']) && isset($filters['order'])) {
            $sortableColumns = ['name', 'parent_id', 'created_at'];
            $sort = in_array($filters['sort'] ?? 'name', $sortableColumns) ? $filters['sort'] : 'name';
            $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sort, $order);
        }
        return $query->paginate($per_page)->appends(request()->query());
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

}