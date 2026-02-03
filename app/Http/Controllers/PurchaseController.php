<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Services\PurchaseService;
use App\Services\SupplierService;
use App\Services\ProductService;
use Illuminate\Http\Request;

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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $purchases = $this->purchaseService->getAllPurchases(15);
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
    public function update(Request $request)
    {
        $this->purchaseService->updatePurchase($request->validated());
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase updated successfully.');
    }
}