<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\StoreProductRequest;
use App\Services\{
    ProductService,
    StockService,
};
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    protected $stockService;

    public function __construct(ProductService $productService, StockService $stockService)
    {
        $this->productService = $productService;
        $this->stockService = $stockService;
    }
    public function exportPdf(Request $request)
    {
        $products = $this->productService->getAllProducts(['per_page' => 1000]); // Get all products without pagination

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

        $products = $this->productService->getAllProducts($filters);
        $categories = $this->productService->getAllCategories();
        $mostSoldProduct = $this->productService->getMostSoldProduct();
        $leastSoldProduct = $this->productService->getLeastSoldProduct();

        return view('products.index', compact('products', 'categories', 'mostSoldProduct', 'leastSoldProduct'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->productService->getAllCategories();
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $this->productService->createProduct($request->validated());

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productService->getProductById($id);
        // For the paginated history table
        $stockMovements = $this->stockService->getStockMovementsForProduct($id, 10);
        // For the evolution chart
        $stockEvolution = $this->stockService->getStockEvolutionForProduct($id);

        // Prepare data for the chart, already in ISO 8601 format from the service
        $chartLabels = json_encode(array_column($stockEvolution, 'x'));
        $chartData = json_encode(array_column($stockEvolution, 'y'));

        return view('products.show', compact('product', 'stockMovements', 'chartLabels', 'chartData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $product = $this->productService->getProductById($id);
        $categories = $this->productService->getAllCategories();
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProductRequest $request, int $id)
    {
        $this->productService->updateProduct($id, $request->validated());

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            $this->productService->deleteProduct($id);

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
            'quantity_stock' => 'stock',
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
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%");
        }

        // Pagination simple, trié par nom et on charge la catégorie pour la vue
        $products = $query->with('category')->orderBy('name')->paginate(20)->withQueryString();

        return view('front-office.products.index', compact('products'));
    }
}
