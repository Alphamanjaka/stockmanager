<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Services\{CategoryService, ProductService, ColorService, ProductColorService, StockService,};
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;
    protected StockService $stockService;
    protected ColorService $colorService;
    protected ProductColorService $productColorService;
    protected CategoryService $categoryService;
    protected SaleService $saleService;


    public function __construct(
        ProductService $productService,
        StockService $stockService,
        ColorService $colorService,
        ProductColorService $productColorService,
        CategoryService $categoryService,
        SaleService $saleService
    ) {
        $this->productService = $productService;
        $this->stockService = $stockService;
        $this->colorService = $colorService;
        $this->productColorService = $productColorService;
        $this->categoryService = $categoryService;
        $this->saleService = $saleService;
    }
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

        // Utilisation de ProductColorService pour obtenir une ligne par variante (Produit + Couleur)
        $products = $this->productColorService->getAllWithRelations($filters);
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
        $colors = $this->colorService->getAllColors([], false);
        return view('products.create', compact('categories', 'colors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $productData = $request->validated();
        $colors = $productData['colors'] ?? [];
        $this->productService->create($productData, $colors);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
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
        $categories = $this->productService->getAll();
        $colors = $this->colorService->getAllColors([], false);
        return view('products.edit', compact('product', 'categories', 'colors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProductRequest $request, int $id)
    {
        $productData = $request->validated();
        $colors = $productData['colors'] ?? [];
        $this->productService->update($id, $productData, $colors);

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
