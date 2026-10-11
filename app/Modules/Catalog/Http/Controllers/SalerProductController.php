<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Services\ProductColorService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SalerProductController extends Controller
{
    public function __construct(protected ProductColorService $productService)
    {}
    /**
     * Affiche la liste des produits pour le vendeur (lecture seule).
     */
    public function index(Request $request)
    {
        $filters=['sort' => 'id', 'order' => 'asc', 'search' => $request->get('search'), 'per_page' => 15];
                // Pagination simple, trié par nom
        $products = $this->productService->getAll($filters);

        return view('catalog::front-office.products.index', compact('products'));
    }
}
