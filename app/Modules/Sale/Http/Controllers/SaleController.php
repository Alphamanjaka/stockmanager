<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Catalog\Services\ProductColorService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Services\CustomerService;

class SaleController extends Controller
{
    /**
     * Initialize the controller.
     */
    public function __construct(
        private SaleService $saleService,
        private ProductColorService $productColorService,
        private CustomerService $customerService
    )
    {
        $this->saleService = $saleService;
        $this->productColorService = $productColorService;
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $currency = settings('currency_symbol', 'Mga');
        $items = $this->productColorService->getAvailableProducts();
        $customers = $this->customerService->getAll([], false);
        return view('sale::sales.create', compact('items', 'currency', 'customers'));
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
                auth()->id(), // Associer la vente à l'utilisateur connecté
                $validated
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
     * Display the seller's own sales with statistics.
     */
    public function index()
    {
        // Un vendeur ne voit que ses propres ventes et ses propres statistiques
        $sales = $this->saleService->getAllSales(15, ['user' => auth()->id()]);
        $stats = $this->saleService->getSalesStatistics(auth()->id());

        return view('sale::sales.index', array_merge(
            compact('sales'),
            $stats
        ));
    }

    /**
     * Display a single sale.
     */
    public function show(int $id)
    {
        $sale = $this->saleService->getSaleForSeller($id, auth()->id());

        return view('sale::sales.show', compact('sale'));
    }
    

    /**
     * Export sale as PDF.
     */
    public function exportPdf(int $id)
    {
        $sale = $this->saleService->getSaleForSeller($id, auth()->id());
        $pdf = Pdf::loadView('sale::sales.pdf', compact('sale'));

        return $pdf->download("facture_{$sale->reference}.pdf");
    }
    
    /*
     * Search sales for global search.
    */
    public function searchForGlobalSearch(string $query, int $limit = 5)
    {
        return $this->saleService->searchForGlobalSearch($query, $limit);
    } 
}