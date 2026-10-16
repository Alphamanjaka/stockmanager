<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ColorService;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Sale\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductColorController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService,
        protected ColorService $colorService,
        protected ProductColorService $productColorService,
        protected SaleService $saleService
    ) {}
    public function exportPdf(Request $request)
    {
        $products = $this->productColorService->getAll([], false); // Toutes les variantes, sans pagination

        $pdf = Pdf::loadView('catalog::products.pdf', compact('products'));

        return $pdf->download('products.pdf');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'sort' => $request->get('sort', 'id'),
            'order' => $request->get('order', 'asc'),
            'search' => $request->get('search'),
            'per_page' => 15,
        ];

        $productVariants = $this->productColorService->getAll($filters);
        $categories = $this->categoryService->getAll();
        $mostSoldProduct = $this->saleService->getMostSoldProduct();
        $leastSoldProduct = $this->saleService->getLeastSoldProduct();

        return view('catalog::products.index', compact('categories', 'mostSoldProduct', 'leastSoldProduct', 'filters', 'productVariants'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->categoryService->getAll(['sort' => 'name'], false);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        // Modèles d'options par catégorie : pré-remplissent les options du produit
        $templates = $categories->mapWithKeys(fn ($category) => [$category->id => $category->option_template ?? []]);

        // Noms d'options suggérés : ceux des modèles de catégorie et quelques classiques
        $optionNames = $categories
            ->flatMap(fn ($category) => collect($category->option_template ?? [])->pluck('name'))
            ->merge(['Couleur', 'Taille', 'Contenance', 'Longueur'])
            ->unique()
            ->values();

        return view('catalog::products.create', compact('categories', 'brands', 'templates', 'optionNames'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $this->productColorService->storeProductWithVariants($request->validated());

            return redirect()->route('admin.products.index')
                ->with('success', 'Product and variants created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            $this->productService->delete($id);

            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Affiche la page de détails du produit.
     * Charge uniquement le produit et les catégories pour le modal d'édition.
     */
    public function show(int $id)
    {
        $product = $this->productService->getById($id); // Ceci est le modèle Product principal
        $variants = $this->productColorService->listByProduct($id); // Ce sont les modèles ProductColor
        $categories = $this->categoryService->getAll([], false); // Toutes les catégories pour l'édition du produit
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return view('catalog::products.show', compact('product', 'variants', 'categories', 'brands'));
    }

    /**
     * Mise à jour des informations générales via AJAX.
     */
    public function updateDetails(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = $this->productService->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Informations générales mises à jour.',
            'data' => [
                'name' => $product->name,
                'description' => $product->description,
                'category_name' => $product->category->name,
            ]
        ]);
    }

    /**
     * Store a new product variant (ProductColor). Elle démarre à un stock de 0 (mouvements de stock).
     */
    public function storeVariant(Request $request, int $productId)
    {
        $validated = $request->validate($this->variantRules());

        try {
            $this->productColorService->createVariant($this->productService->getById($productId), $validated);
            return back()->with('success', 'Variante ajoutée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'ajout de la variante : ' . $e->getMessage());
        }
    }

    /**
     * Update an existing product variant (ProductColor). Le stock n'en fait pas partie (mouvements de stock).
     */
    public function updateVariant(Request $request, int $variantId)
    {
        $validated = $request->validate($this->variantRules());

        try {
            $this->productColorService->updateVariant($variantId, $validated);
            return back()->with('success', 'Variante mise à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de la variante : ' . $e->getMessage());
        }
    }

    /**
     * Options et spécifications du produit (fiche produit).
     */
    public function updateOptions(Request $request, int $product)
    {
        $request->validate([
            'options' => 'nullable|array|max:5',
            'options.*.name' => ['nullable', 'string', 'max:50', 'regex:/^[^\[\]]*$/'],
            'options.*.values' => 'nullable|string|max:1000',
            'specs' => 'nullable|array|max:30',
            'specs.*.key' => 'nullable|string|max:100',
            'specs.*.value' => 'nullable|string|max:1000',
        ]);

        try {
            $this->productColorService->updateOptions(
                $this->productService->getById($product),
                $request->input('options', []),
                $request->input('specs', [])
            );
            return back()->with('success', 'Options et spécifications mises à jour.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** Champs communs à l'ajout et à la modification d'une variante. */
    private function variantRules(): array
    {
        return [
            'values' => 'nullable|array',
            'values.*' => 'nullable|string|max:100',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'price_purchase' => 'nullable|numeric|min:0',
            'alert_stock' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Delete a product variant (ProductColor).
     */
    public function destroyVariant(int $variantId)
    {
        try {
            $this->productColorService->deleteProductColor($variantId);
            return back()->with('success', 'Variante supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression de la variante : ' . $e->getMessage());
        }
    }
}
