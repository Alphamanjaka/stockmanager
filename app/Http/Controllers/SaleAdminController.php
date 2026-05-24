<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Services\ProductColorService;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleAdminController extends Controller
{
    // constructor to inject SaleService and ProductColorService
    public function __construct(protected SaleService $saleService, protected ProductColorService $productColorService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = $this->saleService->getAllSales();
        $stats   = $this->saleService->getSalesStatistics();
        return view('back-office.sales.index', array_merge(compact('sales'), $stats));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $sale = $this->saleService->getSaleById($id);

        return view('back-office.sales.show', compact('sale'));
    }

    /**
     * Export the specified resource as PDF.
     */
    public function exportPdf(int $id)
    {
        $sale = $this->saleService->getSaleById($id);
        $pdf = Pdf::loadView('sales.pdf', compact('sale'));

        return $pdf->download("facture_{$sale->reference}.pdf");
    }

    /**
     * Create a new sale
     */
    public function create(){
        // provide a list of available products with their stock and price for the form
        $items = $this->productColorService->getAvailableProducts();
        return view('back-office.sales.create', compact('items') );
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request){
        try {
            $validated = $request->validated();

            $sale = $this->saleService->createSale(
                $validated['products'],
                $validated['discount'] ?? 0,
                auth()->id() // Associer la vente à l'utilisateur connecté
            );
            return redirect()
                ->route('admin.sales.show', $sale->id)
                ->with('success', "Vente {$sale->reference} validée avec succès !");
        } catch (\Exception $e) {
            return back()
              ->withInput()
                ->with('error', "Erreur lors de la vente : " . $e->getMessage());
        }


    }
}
