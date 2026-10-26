<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Core\Exports\PdfDocument;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Services\CustomerService;
use App\Modules\Sale\Http\Controllers\Concerns\ChecksOutSales;
use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Sale\Services\AbcAnalysisService;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Sale\Support\PosCatalog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $filters = $request->only(['search', 'date_from', 'date_to']) + ['customer' => $customer?->id];
        $sales = $this->saleService->getAllSales(15, $filters)->withQueryString();
        $stats = $this->saleService->getSalesStatistics();
        // Marge : back office seulement (les vendeurs ne voient pas les coûts)
        $margin = $this->saleService->getGrossMargin();

        return view('sale::back-office.sales.index', array_merge(compact('sales', 'customer', 'margin'), $stats));
    }

    /**
     * Analyse ABC : variantes classées par chiffre d'affaires sur la période (?days=), filtrables par classe (?class=).
     */
    public function abc(Request $request, AbcAnalysisService $abc)
    {
        $analysis = $abc->analyse($request->integer('days', AbcAnalysisService::DEFAULT_PERIOD));
        $class = in_array($request->query('class'), AbcAnalysisService::CLASSES, true) ? $request->query('class') : null;

        $lines = $class ? $analysis['lines']->where('class', $class)->values() : $analysis['lines'];
        $page = LengthAwarePaginator::resolveCurrentPage();
        $lines = (new LengthAwarePaginator($lines->forPage($page, 25)->values(), $lines->count(), 25, $page, [
            'path' => $request->url(),
        ]))->withQueryString();

        return view('sale::back-office.sales.abc', compact('analysis', 'class', 'lines'));
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
        $pdf = PdfDocument::load('sale::sales.pdf', compact('sale'));

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
