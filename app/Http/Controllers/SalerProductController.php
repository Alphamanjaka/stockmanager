<?php

namespace App\Http\Controllers;

use App\Services\ProductColorService;
use Illuminate\Http\Request;

class SalerProductController extends Controller
{
    public function __construct(private ProductColorService $productService)
    {
        $this->productService = $productService;
    }
    /**
     * Affiche la liste des produits pour le vendeur (lecture seule).
     */
    public function index(Request $request)
    {
        $filters=['sort' => 'id', 'order' => 'asc', 'search' => $request->get('search'), 'per_page' => 15];
                // Pagination simple, trié par nom
        $products = $this->productService->getAll($filters);

        return view('front-office.products.index', compact('products'));
    }
}
