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
        $currency = settings('currency_symbol', 'Mga');
        $items = $this->productColorService->getAvailableProducts();
        return view('sales.create', compact('items','currency'));
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
    public function show(int $id)
    {
        $sale = $this->saleService->getSaleById($id);

        // Exemple d'appel direct au helper settings
        $company_name = settings('company_name', 'StockMaster Pro');
        $currency_symbol = settings('currency_symbol', 'Mga');
        $company_address = settings('company_address', 'Antananarivo, Madagascar');
        $company_phone = settings('company_phone', '+261 34 22 12345');
        $company_email = settings('company_email', '');
        return view('sales.show', compact('sale', 'company_name', 'currency_symbol', 'company_address', 'company_phone', 'company_email'));
    }

    /**
     * Export sale as PDF.
     */
    public function exportPdf(int $id)
    {
        $sale = $this->saleService->getSaleById($id);
        $pdf = Pdf::loadView('sales.pdf', compact('sale'));

        return $pdf->download("facture_{$sale->reference}.pdf");
    }
}
