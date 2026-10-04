<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreProductRequest;
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
        $products = $this->productColorService->getAll(['per_page' => 50000]); // Get all products without pagination

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
        $categories = $this->categoryService->getAll([], false);
        $colors = $this->colorService->getAll([], false);
        return view('catalog::products.create', compact('categories', 'colors'));
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
        $colors = $this->colorService->getAll([], false); // Toutes les couleurs pour la création/édition de variantes

        // Passe toutes les données nécessaires à la vue pour le rendu initial
        return view('catalog::products.show', compact('product', 'variants', 'categories', 'colors'));
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

    public function edit(int $id)
    {
        $item = $this->productService->getById($id);
        $categories = $this->categoryService->getAll();

        return view('catalog::products.edit', compact('item', 'categories'));
    }

    /**
     * Store a new product variant (ProductColor).
     */
    public function storeVariant(Request $request, int $productId)
    {
        $validated = $request->validate([
            'color_id' => 'required|exists:colors,id',
            'stock' => 'required|integer|min:0',
            'price_purchase' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'alert_stock' => 'nullable|integer|min:0',
        ]);

        try {
            $this->productColorService->createProductColor(array_merge($validated, ['product_id' => $productId]));
            return back()->with('success', 'Variante ajoutée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'ajout de la variante : ' . $e->getMessage());
        }
    }

    /**
     * Update an existing product variant (ProductColor).
     */
    public function updateVariant(Request $request, int $variantId)
    {
        $validated = $request->validate([
            'color_id' => 'required|exists:colors,id', // Allow changing color, but ensure it's unique for the product
            'stock' => 'required|integer|min:0',
            'price_purchase' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'alert_stock' => 'nullable|integer|min:0',
        ]);

        try {
            $this->productColorService->updateProductColor($variantId, $validated);
            return back()->with('success', 'Variante mise à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de la variante : ' . $e->getMessage());
        }
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
