<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;

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
        $category = $this->categoryService->getCategoryById((int)$id);
        if (!$category) {
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
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Delete category by ID
        $deleted = $this->categoryService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Category not found or cannot be deleted'], 404);
        }
        return response()->json(['message' => 'Category deleted successfully']);

    }
}