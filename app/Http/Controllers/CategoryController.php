<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
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
        $stats = $this->categoryService->getCategoryStats();
        return view("categories.index", compact("categories", "stats"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $categories = $this->categoryService->getCategoriesList(); // On récupère les objets complets
        $categoriesChildrenAvalaibles = $this->categoryService->getAvailableChildren();
        return view("categories.create", compact("categories", "categoriesChildrenAvalaibles"));
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
            $data = $request->validated();
            $data['children'] = $request->input('children', []); // On ajoute les enfants manuellement
            $this->categoryService->create($data);

            return redirect()->route("admin.categories.index")->with("success", "Category created successfully.");
        } catch (\Exception $e) {
            Log::error("Erreur création catégorie : " . $e->getMessage());
            return redirect()->back()->with("error", "An unexpected error occurred. Please try again.")->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = $this->categoryService->getCategoryById($id);
        $products = $this->categoryService->getProductsByCategory($id);

        // Calcul de la valeur du stock pour cette catégorie
        $stockValue = $products->sum(function ($product) {
            return $product->price * $product->quantity_stock;
        });

        return view("categories.show", compact("category", "products", "stockValue"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // edit category
        $category = $this->categoryService->getCategoryById($id);
        // get all categories
        $categoriesParent = $this->categoryService->getCategoriesList();
        // get available children for this category
        $categories = $this->categoryService->getAvailableChildren(null); // On exclut la catégorie elle-même pour éviter les boucles
        return view("categories.edit", compact("category", "categories", "categoriesParent"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            // We only pass validated data to the service for security.
            $data = $request->validated();
            $data['children'] = $request->input('children', []);
            $this->categoryService->update($id, $data);
            return redirect()->route("admin.categories.edit", $id)->with("success", "Catégorie mise à jour avec succès.");
        } catch (\Exception $e) {
            Log::error("Erreur mise à jour catégorie : " . $e->getMessage());
            return redirect()->route("admin.categories.edit", $id)
                ->with("error", "Une erreur est survenue lors de la mise à jour : " . $e->getMessage())
                ->withInput();
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
            Log::error("Erreur suppression catégorie : " . $e->getMessage());
            return redirect()->back()->with("error", "Une erreur est survenue lors de la suppression de la catégorie.");
        }
    }
}
