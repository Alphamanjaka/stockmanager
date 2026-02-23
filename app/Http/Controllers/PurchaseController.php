<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Services\{
    PurchaseService,
    SupplierService,
    ProductService
};
use App\Http\Resources\PurchaseApiResourceCollection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    protected $purchaseService;
    protected $supplierService;
    protected $productService;

    public function __construct(
        PurchaseService $purchaseService,
        SupplierService $supplierService,
        ProductService $productService
    ) {
        $this->purchaseService = $purchaseService;
        $this->supplierService = $supplierService;
        $this->productService = $productService;
    }
    // Dans app/Http/Controllers/PurchaseController.php

    public function getPurchasesApi(Request $request)
    {
        // Délégation de la logique métier au service
        $filters = [
            'search' => $request->get('search'),
            'sort' => $request->get('sort'),
        ];
        $purchases = $this->purchaseService->getPurchasesForApi($filters, $request->get('size', 10));

        // On utilise une collection de ressources personnalisée qui retourne le format exact attendu par Tabulator.
        return new PurchaseApiResourceCollection($purchases);
    }




    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $purchases = $this->purchaseService->getAllPurchases(15, $request->only(['search', 'state']));
        $stats = $this->purchaseService->getPurchaseStatistics();

        return view('purchases.index', array_merge(
            compact('purchases'),
            $stats
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = $this->productService->getAvailableProducts();
        $suppliers = $this->supplierService->getAllSuppliers();
        return view('purchases.create', compact('products', 'suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $data = $request->validated();

        try {
            $purchase = $this->purchaseService->processPurchase(
                $data['supplier_id'],
                $data['products']
            );

            return redirect()->route('admin.purchases.index')
                ->with('success', "L'achat {$purchase->reference} a été enregistré. Le stock a été mis à jour.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        $products = $this->productService->getAllProducts();
        $suppliers = $this->supplierService->getAllSuppliers();
        return view('purchases.edit', compact('purchase', 'products', 'suppliers'));
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Delete logic can be added to PurchaseService if needed
        return back()->with('info', 'Delete not fully implemented yet.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        try {
            $this->purchaseService->updatePurchase($id, $validated);
            return redirect()->route('admin.purchases.show', $id)->with('success', 'L\'achat a été mis à jour.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de l\'achat.');
        }
    }

    /**
     * Export the specified purchase to PDF.
     */
    public function exportPdf($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'));

        return $pdf->download('achat_' . $purchase->reference . '.pdf');
    }

    /**
     * Update the state of the specified purchase.
     */
    public function updateState(Request $request, $id)
    {
        $validated = $request->validate([
            'state' => ['required', Rule::in(['Draft', 'Ordered', 'Received', 'Paid'])],
        ]);

        $purchase = $this->purchaseService->getPurchaseById($id);

        try {
            // Special logic for 'Received' state to update stock
            if ($validated['state'] === 'Received' && $purchase->state !== 'Received') {
                $this->purchaseService->markAsReceived($purchase);
            } else {
                $purchase->update(['state' => $validated['state']]);
            }

            return back()->with('success', "Le statut de l'achat #{$purchase->reference} a été mis à jour.");
        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors du changement de statut : " . $e->getMessage());
        }
    }
}
