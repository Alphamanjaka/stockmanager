<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ColorService;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Catalog\Support\Pricing;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductColorController extends Controller
{
    use ArchivesRecords;

    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService,
        protected ColorService $colorService,
        protected ProductColorService $productColorService,
        protected SaleService $saleService
    ) {}

    /**
     * Liste des produits, une ligne par variante (même requête que l'export : ProductsExport).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category', 'status', 'sort', 'order']);

        $productVariants = $this->productColorService->listQuery($filters)->paginate(15)->withQueryString();
        $categories = $this->categoryService->getCategoriesList();
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
        $brands = Brand::active()->orderBy('name')->get(['id', 'name']);

        // Modèles d'options par catégorie : pré-remplissent les options du produit
        $templates = $categories->mapWithKeys(fn ($category) => [$category->id => $category->option_template ?? []]);

        // Noms d'options suggérés : ceux des modèles de catégorie et quelques classiques
        $optionNames = $categories
            ->flatMap(fn ($category) => collect($category->option_template ?? [])->pluck('name'))
            ->merge(['Couleur', 'Taille', 'Contenance', 'Longueur'])
            ->unique()
            ->values();

        // Marge par défaut (Paramètres) : le prix de vente se remplit pendant la saisie du coût
        $margin = Pricing::defaultMargin();

        return view('catalog::products.create', compact('categories', 'brands', 'templates', 'optionNames', 'margin'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $this->productColorService->storeProductWithVariants($request->validated());

            return redirect()->route('admin.products.index')
                ->with('success', __('Product and variants created.'));
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Suppression définitive d'un produit archivé et jamais utilisé : un produit dont une variante a été
     * vendue, achetée, comptée ou a bougé en stock reste archivé (la base refuse).
     */
    public function destroy(int $id)
    {
        return $this->deletePermanently($id, 'admin.products.index');
    }

    /** Archivage par produit : ses variantes disparaissent avec lui des listes, de la caisse et des achats. */
    protected function archiveTargets(array $ids): Builder
    {
        return Product::whereKey($ids);
    }

    /**
     * Affiche la page de détails du produit.
     * Charge uniquement le produit et les catégories pour le modal d'édition.
     */
    public function show(int $id)
    {
        $product = $this->productService->getById($id); // Ceci est le modèle Product principal
        $variants = $this->productColorService->listByProduct($id); // Ce sont les modèles ProductColor
        // Catégories et marques en service, plus celles déjà portées par le produit (même archivées) :
        // les formulaires d'édition ne doivent pas les perdre
        $categories = $this->categoryService->getAll([], false);
        if ($product->category?->isArchived()) {
            $categories->push($product->category);
        }
        $brands = Brand::where(fn ($query) => $query->active()->orWhereIn('id', $variants->pluck('brand_id')->filter()))
            ->orderBy('name')
            ->get(['id', 'name']);

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
            ],
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
            return back()->with('error', 'Erreur lors de l\'ajout de la variante : '.$e->getMessage());
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
            return back()->with('error', 'Erreur lors de la mise à jour de la variante : '.$e->getMessage());
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
            return back()->with('error', 'Erreur lors de la suppression de la variante : '.$e->getMessage());
        }
    }
}
