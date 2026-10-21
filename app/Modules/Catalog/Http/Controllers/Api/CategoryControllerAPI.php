<?php

namespace App\Modules\Catalog\Http\Controllers\Api;

use App\Core\Services\ArchiveService;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;

class CategoryControllerAPI extends Controller
{
    // constructor with dependency injection for CategoryService
    public function __construct(protected CategoryService $categoryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return CategoryResource::collection($this->categoryService->getAllCategory());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        // Validation is handled by StoreCategoryRequest
        $category = $this->categoryService->create($request->validated());

        return new CategoryResource($category);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find category by ID and return it as a resource
        $category = $this->categoryService->getCategoryById((int) $id);
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        // Validation is handled by UpdateCategoryRequest
        $category = $this->categoryService->update($id, $request->validated());
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Mêmes règles que le back office : seule une catégorie archivée et vide se supprime définitivement
        $result = app(ArchiveService::class)->forceDelete(Category::whereKey((int) $id));

        if ($result['done'] > 0) {
            return response()->json(['message' => 'Category deleted successfully']);
        }

        return response()->json([
            'message' => $result['skipped']
                ? 'Category cannot be deleted: '.implode(' ; ', $result['skipped'])
                : 'Only an archived category can be deleted: archive it first.',
        ], 422);
    }
}
