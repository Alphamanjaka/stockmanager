<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Sale\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Services\CustomerService;
use Illuminate\Http\Request;

class SaleAdminController extends Controller
{
    // constructor to inject SaleService and ProductColorService
    public function __construct(
        protected SaleService $saleService,
        protected ProductColorService $productColorService,
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ?customer=ID : historique des ventes d'un client (lien depuis la fiche client)
        $customer = $request->filled('customer') ? Customer::find($request->integer('customer')) : null;

        $sales = $this->saleService->getAllSales(15, ['customer' => $customer?->id])->withQueryString();
        $stats   = $this->saleService->getSalesStatistics();
        return view('sale::back-office.sales.index', array_merge(compact('sales', 'customer'), $stats));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $sale = $this->saleService->getSaleById($id);

        return view('sale::back-office.sales.show', compact('sale'));
    }

    /**
     * Export the specified resource as PDF.
     */
    public function exportPdf(int $id)
    {
        $sale = $this->saleService->getSaleById($id);
        $pdf = Pdf::loadView('sale::sales.pdf', compact('sale'));

        return $pdf->download("facture_{$sale->reference}.pdf");
    }

    /**
     * Create a new sale
     */
    public function create(){
        // provide a list of available products with their stock and price for the form
        $items = $this->productColorService->getAvailableProducts();
        $customers = $this->customerService->getAll([], false);
        return view('sale::back-office.sales.create', compact('items', 'customers'));
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
                auth()->id(), // Associer la vente à l'utilisateur connecté
                $validated
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
