<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ProductService;

class SalerProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    /**
     * Affiche la liste des produits pour le vendeur (lecture seule).
     */
    public function index(Request $request)
    {
        $filters=['sort' => 'name', 'order' => 'asc', 'search' => $request->get('search'), 'per_page' => 15];
                // Pagination simple, trié par nom
        $products = $this->productService->getAllProducts($filters);

        return view('front-office.products.index', compact('products'));
    }
}
