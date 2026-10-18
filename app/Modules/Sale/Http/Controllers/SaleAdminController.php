<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Modules\Sale\Http\Controllers\Concerns\ChecksOutSales;
use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Sale\Support\PosCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Services\CustomerService;
use Illuminate\Http\Request;

class SaleAdminController extends Controller
{
    use ChecksOutSales;

    public function __construct(
        protected SaleService $saleService,
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
     * Reçu de caisse (ticket 80 mm) à imprimer.
     */
    public function receipt(int $id)
    {
        $sale = $this->saleService->getSaleById($id);

        return view('sale::sales.receipt', compact('sale'));
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
     * Écran de caisse.
     */
    public function create(PosCatalog $catalog)
    {
        $pos = $catalog->build();
        $customers = $this->customerService->getAll([], false);

        return view('sale::back-office.sales.create', compact('pos', 'customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        return $this->checkout($request, 'admin.sales.');
    }
}
