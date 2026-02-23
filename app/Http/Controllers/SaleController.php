<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\{
    SaleService,
    ProductService
};
use App\Http\Requests\StoreSaleRequest;

class SaleController extends Controller
{
    protected $saleService;
    protected $productService;

    public function __construct(SaleService $saleService, ProductService $productService)
    {
        $this->saleService = $saleService;
        $this->productService = $productService;
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $products = $this->productService->getAvailableProducts();
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
                $validated['discount'] ?? 0
            );

            return redirect()
                ->route('sales.show', $sale->id)
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
