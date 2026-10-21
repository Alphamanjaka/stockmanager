<?php

namespace App\Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Stock\Http\Requests\StoreStockMovementRequest;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(protected StockService $stockService, protected ProductColorService $productColorService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $stockMovements = $this->stockService->getAllStockMovements($filters, 15);
        $dormantProducts = $this->stockService->getDormantProducts();
        $rotationStats = $this->stockService->getRotationStats();
        $stockValueEvolution = $this->stockService->getTotalStockValueEvolution();
        $variantsWithoutCost = $this->stockService->countStockWithoutCost();

        return view('stock::stock_movements.index', compact('stockMovements', 'dormantProducts', 'rotationStats', 'stockValueEvolution', 'variantsWithoutCost'));
    }

    /**
     * Formulaire de correction de stock (secours en cas d'erreur de saisie).
     * ?variant=<id> présélectionne une variante (lien depuis la fiche produit).
     */
    public function create(Request $request)
    {
        $productColors = $this->productColorService->getAll([], false);
        $selectedId = $request->integer('variant') ?: null;

        return view('stock::stock_movements.create', compact('productColors', 'selectedId'));
    }

    /**
     * Enregistre une correction de stock : le stock ne change que par ce mouvement historisé.
     */
    public function store(StoreStockMovementRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $this->stockService->correct(
                $validatedData['product_color_id'],
                $validatedData['type'],
                $validatedData['quantity'],
                $validatedData['reason']
            );

            return redirect()->route('admin.movements.index')->with('success', 'Correction de stock enregistrée.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display a single stock movement.
     */
    public function show(int $id)
    {
        $stockMovement = $this->stockService->getStockMovementById($id);

        return view('stock::stock_movements.show', compact('stockMovement'));
    }
}
