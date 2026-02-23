<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Services\{
    ProductService,
    StockService,
};
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
}
