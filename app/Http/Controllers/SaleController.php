<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Services\{SaleService, ProductColorService};
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /**
     * Initialize the controller.
     */
    public function __construct(private SaleService $saleService, private ProductColorService $productColorService)
    {
        $this->saleService = $saleService;
        $this->productColorService = $productColorService;
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $products = $this->productColorService->getAvailableProducts();
        return view('sales.create', compact('products'));
    }

    /**
     * Store a newly created sale.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $validated = $request->validated();

            $sale = $this->saleService->createSale(
                $validated['products'],
                $validated['discount'] ?? 0,
                auth()->id() // Associer la vente à l'utilisateur connecté
            );

            return redirect()
                ->route('saler.show', $sale->id)
                ->with('success', "Vente {$sale->reference} validée avec succès !");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', "Erreur lors de la vente : " . $e->getMessage());
        }
    }

    /**
     * Display all sales with statistics.
     */
    public function index()
    {
        $sales = $this->saleService->getAllSales(15);
        $stats = $this->saleService->getSalesStatistics();

        return view('sales.index', array_merge(
            compact('sales'),
            $stats
        ));
    }

    /**
     * Display a single sale.
     */
    public function show($id)
    {
        $sale = $this->saleService->getSaleById($id);
        return view('sales.show', compact('sale'));
    }

    /**
     * Export sale as PDF.
     */
    public function exportPdf($id)
    {
        $sale = $this->saleService->getSaleById($id);
        $pdf = Pdf::loadView('sales.pdf', compact('sale'));

        return $pdf->download("facture_{$sale->reference}.pdf");
    }
}
