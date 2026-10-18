<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Modules\Catalog\Support\VariantOptions;
use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    use ArchivesRecords;

    public function __construct(protected CategoryService $categoryService){}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'sort' => $request->get('sort', 'name'),
            'order' => $request->get('order', 'asc'),
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'per_page' => 15,
        ];
        //
        $categories = $this->categoryService->getAllCategory($filters);
        $stats = $this->categoryService->getCategoryStats();
        $distribution = $this->categoryService->getParentCategoryDistribution();
        return view("catalog::categories.index", compact("categories", "stats", "distribution"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $categories = $this->categoryService->getCategoriesList(); // On récupère les objets complets
        $categoriesChildrenAvalaibles = $this->categoryService->getAvailableChildren();
        return view("catalog::categories.create", compact("categories", "categoriesChildrenAvalaibles"));
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
            // Modèle d'options : nettoyé (valeurs « a, b » → liste), null si aucune option
            $data['option_template'] = VariantOptions::normalizeOptions($request->input('option_template', [])) ?: null;
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

        return view("catalog::categories.show", compact("category", "products", "stockValue"));
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
        return view("catalog::categories.edit", compact("category", "categories", "categoriesParent"));
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
            // Modèle d'options : nettoyé (valeurs « a, b » → liste), null si aucune option
            $data['option_template'] = VariantOptions::normalizeOptions($request->input('option_template', [])) ?: null;
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
     * Suppression définitive d'une catégorie archivée et vide : une catégorie qui contient des produits
     * (même archivés) reste archivée, la base refuse (avant, ses produits étaient supprimés avec elle).
     */
    public function destroy(string $id)
    {
        return $this->deletePermanently((int) $id, 'admin.categories.index');
    }

    protected function archiveTargets(array $ids): Builder
    {
        return Category::whereKey($ids);
    }
}
