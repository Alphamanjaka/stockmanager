<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductColorService;
use Illuminate\Http\Request;

class SalerProductController extends Controller
{
    public function __construct(protected ProductColorService $productService) {}

    /**
     * Affiche la liste des produits pour le vendeur (lecture seule).
     */
    public function index(Request $request)
    {
        // Même liste que le back office, produits en service uniquement, triée par nom
        $products = $this->productService->listQuery(['search' => $request->get('search')])
            ->paginate(15)
            ->withQueryString();

        return view('catalog::front-office.products.index', compact('products'));
    }
}
