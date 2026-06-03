<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Services\{StockService};
use App\Services\ProductColorService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(protected StockService $stockService,protected ProductColorService $productColorService)
    {}

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

        return view('stock_movements.index', compact('stockMovements', 'dormantProducts', 'rotationStats', 'stockValueEvolution'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productColors = $this->productColorService->getAll([], false);
        return view('stock_movements.create', compact('productColors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStockMovementRequest $request)
    {
        $validatedData = $request->validated();

        try {
            if ($validatedData['type'] === 'in') {
                $this->stockService->addStock(
                    $validatedData['product_color_id'],
                    $validatedData['quantity'],
                    $validatedData['reason'] ?? 'Ajustement manuel'
                );
            } else {
                $this->stockService->removeStock(
                    $validatedData['product_color_id'],
                    $validatedData['quantity'],
                    $validatedData['reason'] ?? 'Ajustement manuel'
                );
            }

            return redirect()->back()->with('success', 'Mouvement de stock créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display a single stock movement.
     */
    public function show(int $id)
    {
        $stockMovement = $this->stockService->getStockMovementById($id);
        return view('stock_movements.show', compact('stockMovement'));
    }
}