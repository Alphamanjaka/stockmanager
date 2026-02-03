<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    protected $categoryService;
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'sort' => $request->get('sort', 'name'),
            'order' => $request->get('order', 'asc'),
            'search' => $request->get('search'),
            'per_page' => 15,
        ];
        //
        $categories = $this->categoryService->getAllCategory($filters);
        return view("categories.index", compact("categories"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $categories = $this->categoryService->getAllCategory();
        return view("categories.create", compact("categories"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        // The validation is handled by StoreCategoryRequest.
        // If it fails, Laravel automatically redirects back with errors.
        try {
            // We only pass validated data to the service for security.
            $this->categoryService->create($request->validated());

            return redirect()->route("admin.categories.index")->with("success", "Category created successfully.");
        } catch (\Exception $e) {
            // \Log::error($e->getMessage()); // It's good practice to log the actual error for debugging.
            return redirect()->back()->with("error", "An unexpected error occurred. Please try again.")->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // detail of category
        $category = Category::findOrFail($id);
        return view("categories.show", compact("category"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // edit category
        $category = Category::findOrFail($id);
        $categories = $this->categoryService->getAllCategory();
        return view("categories.edit", compact("category", "categories"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCategoryRequest $request, string $id)
    {
        try {
            // We only pass validated data to the service for security.
            $this->categoryService->update($id, $request->validated());
            return redirect()->route("admin.categories.index")->with("success", "Category updated successfully.");
        } catch (\Exception $e) {
            // \Log::error($e->getMessage()); // It's good practice to log the actual error for debugging.
            return redirect()->back()->with("error", "An unexpected error occurred. Please try again.")->withInput();
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::withCount('products')->findOrFail($id);

            if ($category->products_count > 0) {
                return redirect()->route('admin.categories.index')
                    ->with('error', "Impossible de supprimer la catégorie « {$category->name} » car elle est associée à {$category->products_count} produit(s).");
            }
            $this->categoryService->delete($id);
            return redirect()->route("admin.categories.index")->with("success", "Category deleted successfully.");
        } catch (\Exception $e) {
            // \Log::error($e->getMessage()); // It's good practice to log the actual error for debugging.
            return redirect()->back()->with("error", "Une erreur est survenue lors de la suppression de la catégorie.");
        }
    }
}
