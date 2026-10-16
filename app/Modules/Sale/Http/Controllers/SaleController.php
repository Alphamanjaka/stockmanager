<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Modules\Sale\Http\Controllers\Concerns\ChecksOutSales;
use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Sale\Support\PosCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Services\CustomerService;

class SaleController extends Controller
{
    use ChecksOutSales;

    /**
     * Initialize the controller.
     */
    public function __construct(
        private SaleService $saleService,
        private CustomerService $customerService
    ) {}

    /**
     * Écran de caisse.
     */
    public function create(PosCatalog $catalog)
    {
        $pos = $catalog->build();
        $customers = $this->customerService->getAll([], false);

        return view('sale::sales.create', compact('pos', 'customers'));
    }

    /**
     * Store a newly created sale.
     */
    public function store(StoreSaleRequest $request)
    {
        return $this->checkout($request, 'saler.');
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
     * Reçu de caisse (ticket 80 mm) à imprimer.
     */
    public function receipt(int $id)
    {
        $sale = $this->saleService->getSaleForSeller($id, auth()->id());

        return view('sale::sales.receipt', compact('sale'));
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
