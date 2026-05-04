<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Services\{ProductColorService, StockManagementService, ProductService, ColorService};
use App\Services\CategoryService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductColorController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ColorService $colorService,
        protected ProductColorService $productColorService,
        protected StockManagementService $stockManagementService,
        protected CategoryService $categoryService,
        protected SaleService $saleService,
        protected StockManagementService $stockService
    ) {}

    /**
     * Liste toutes les variantes (liaisons produit-couleur).
     */
    public function index(Request $request)
    {
        $filters = [
            'sort' => $request->get('sort', 'created_at'),
            'order' => $request->get('order', 'asc'),
            'search' => $request->get('search'),
            'color' => $request->get('color'),
            'per_page' => 15,
        ];

        $products = $this->productColorService->getAllWithRelations($filters);
        $mostSoldProduct = $this->saleService->getMostSoldProduct();
        $leastSoldProduct = $this->saleService->getLeastSoldProduct();
        $categories = $this->categoryService->getAll();


        return view('products.index', compact('products', 'filters', 'mostSoldProduct', 'leastSoldProduct', 'categories'));
    }

    /**
     * Affiche le formulaire pour lier une couleur à un produit.
     */
    public function create()
    {
        $products = $this->productService->getAll([], false);
        $colors = $this->colorService->getAllColors([], false);
        $categories = $this->categoryService->getAll();
        return view('products.create', compact('products', 'colors', 'categories'));
    }

    /**
     * Enregistre la liaison (ProductColor) avec le stock initial.
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
     * Display the specified resource.
     */
    public function show(int $id)
    {
        // Le service gère maintenant le chargement des relations complexes
        $item = $this->productColorService->getById($id);

        // For the paginated history table
        $stockMovements = $this->stockService->getStockMovementsForProduct($id, 10);

        $stockEvolution = $item ? $this->stockService->getStockEvolutionForVariant($item->id) : [];

        $chartLabels = json_encode(array_column($stockEvolution, 'x'));
        $chartData = json_encode(array_column($stockEvolution, 'y'));
        $categories = $this->categoryService->getAll();

        return view('products.show', compact('item', 'stockMovements', 'chartLabels', 'chartData', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $item = $this->productColorService->getById($id);
        $categories = $this->categoryService->getAll();
        $colors = $this->colorService->getAllColors([], false);
        return view('products.edit', compact('item', 'categories', 'colors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProductRequest $request, int $id)
    {
        // On récupère la variante pour trouver le produit associé
        $variant = $this->productColorService->getById($id);

        // Mise à jour des informations générales du produit
        $this->productService->update($variant->product_id, $request->validated());

        // Si vous avez besoin de mettre à jour la variante spécifique ici :
        $variant->update($request->only(['stock', 'price', 'alert_stock']));

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Mise à jour du stock de la variante via AJAX.
     */
    public function updateStock(Request $request, int $id)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'alert_stock' => 'required|integer|min:0',
        ]);

        $item = $this->productColorService->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'État du stock mis à jour.',
            'data' => [
                'stock' => $item->stock,
                'alert_stock' => $item->alert_stock,
            ]
        ]);
    }

    /**
     * Mise à jour du prix de la variante via AJAX.
     */
    public function updatePrice(Request $request, int $id)
    {
        $validated = $request->validate(['price' => 'required|numeric|min:0']);
        $item = $this->productColorService->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Prix de vente mis à jour.',
            'data' => ['price' => $item->price]
        ]);
    }
}