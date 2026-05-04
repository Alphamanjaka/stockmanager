<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Services\{CategoryService, ProductService, ColorService, ProductColorService, StockService, StockManagementService};
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService,
        protected StockService $stockService,
        protected ColorService $colorService,
        protected ProductColorService $productColorService,
        protected SaleService $saleService,
        protected StockManagementService $stockManagementService
    ) {}
    public function exportPdf(Request $request)
    {
        $products = $this->categoryService->getAll(['per_page' => 1000]); // Get all products without pagination

        $pdf = Pdf::loadView('products.pdf', compact('products'));

        return $pdf->download('products.pdf');
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
            'category' => $request->get('category'),
            'per_page' => 15,
        ];

        $products = $this->productService->getAll($filters);
        $categories = $this->categoryService->getAll();
        $mostSoldProduct = $this->saleService->getMostSoldProduct();
        $leastSoldProduct = $this->saleService->getLeastSoldProduct();

        return view('products.index', compact('products', 'categories', 'mostSoldProduct', 'leastSoldProduct', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->categoryService->getAll();
        return view('products.create', compact('categories'));
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
     * Display the specified resource.
     */
    public function show(int $id)
    {
        // Le service gère maintenant le chargement des relations complexes
        $item = $this->productColorService->getProductById($id);

        // For the paginated history table
        $stockMovements = $this->stockService->getStockMovementsForProduct($id, 10);

        $stockEvolution = $item ? $this->stockService->getStockEvolutionForVariant($item->id) : [];

        $chartLabels = json_encode(array_column($stockEvolution, 'x'));
        $chartData = json_encode(array_column($stockEvolution, 'y'));

        return view('products.show', compact('item', 'stockMovements', 'chartLabels', 'chartData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $product = $this->productService->getById($id);
        $categories = $this->categoryService->getAll();
        $colors = $this->colorService->getAllColors([], false);
        return view('products.edit', compact('product', 'categories', 'colors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProductRequest $request, int $id)
    {
        $productData = $request->validated();
        $this->productService->update($id, $productData);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
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
     * Mise à jour des détails globaux du produit via AJAX.
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

    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $mapping = [
            'name'           => 'nom',      // 'colonne_db' => 'entete_csv'
            'price'          => 'prix',
            'category_id'    => 'id_categorie'
        ];

        $rules = [
            'nom'  => 'required',
            'prix' => 'required|numeric',
        ];

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\GenericImport(\App\Models\Product::class, $mapping, $rules),
                $request->file('file')
            );

            return back()->with('success', 'Importation terminée !');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Affiche la liste des produits pour le vendeur (lecture seule).
     */
    public function salerIndex(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'per_page' => 20,
            'sort' => 'name',
            'order' => 'asc'
        ];

        $products = $this->productService->getAll($filters);

        return view('front-office.products.index', compact('products'));
    }
}
