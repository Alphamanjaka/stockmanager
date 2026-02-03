<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
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

        return view('products.index', compact('products', 'categories'));
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
            ->with('success', 'le produit a été créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productService->getProductById($id);
        return view('products.show', compact('product'));
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
            ->with('success', 'le produit a été modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->productService->deleteProduct($id);

        return redirect()->route('admin.products.index')
            ->with('success', 'le produit a été supprimé avec succès.');
    }
}
