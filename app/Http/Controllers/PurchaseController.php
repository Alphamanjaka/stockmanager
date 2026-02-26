<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Services\{
    PurchaseService,
    StockService,
    SupplierService,
    ProductService
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * Affiche la page de création de commandes à partir des ruptures de stock.
     */
    public function createFromShortage()
    {
        $groupedProducts = $this->purchaseService->getShortageProductsGroupedBySupplier();

        return view('purchases.create_from_shortage', [
            'groupedProducts' => $groupedProducts
        ]);
    }

    /**
     * Crée les commandes soumises depuis la page de suggestion.
     */
    public function storeFromShortage(Request $request)
    {
        $submittedItems = $request->input('items', []);
        $itemsToOrder = [];

        // Filtrer et valider les produits sélectionnés par l'utilisateur
        foreach ($submittedItems as $productId => $item) {
            if (isset($item['selected']) && filter_var($item['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                $itemsToOrder[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'supplier_id' => $item['supplier_id'],
                ];
            }
        }

        if (empty($itemsToOrder)) {
            return redirect()->back()->with('warning', 'Aucun produit n\'a été sélectionné ou les quantités étaient invalides.');
        }

        // Regrouper par fournisseur pour créer un bon de commande par fournisseur
        $itemsBySupplier = collect($itemsToOrder)->groupBy('supplier_id');

        $createdPurchases = [];
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($itemsBySupplier as $supplierId => $items) {
                // Ignorer les produits sans fournisseur valide
                if (empty($supplierId)) {
                    $skippedCount += $items->count();
                    continue;
                }

                $purchase = $this->purchaseService->processPurchase($supplierId, $items->toArray());
                $createdPurchases[] = $purchase;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Échec de la création des commandes depuis la rupture de stock: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création des commandes. Veuillez réessayer.');
        }

        $message = count($createdPurchases) . ' commande(s) d\'achat créée(s) avec succès.';
        if ($skippedCount > 0) {
            $message .= " $skippedCount produit(s) ont été ignorés car aucun fournisseur n'était associé.";
        }

        // Rediriger vers la liste des achats avec un message de succès
        return redirect()->route('admin.purchases.index')->with('success', $message);
    }


    public function getPurchasesApi(Request $request)
    {
        // On passe tous les paramètres de la requête (filtres, tri, page) au service
        $params = $request->all();
        $purchases = $this->purchaseService->getPurchasesForApi($params, $request->get('size', 15));

        return new PurchaseApiResourceCollection($purchases);
    }




    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $stats = $this->purchaseService->getPurchaseStatistics();
        $stateCounts = $this->purchaseService->getPurchaseStateCounts();

        return view('purchases.index', array_merge(
            compact('stateCounts'),
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
        $this->purchaseService->deletePurchase($id);
        return redirect()->route('admin.purchases.index')->with('success', 'L\'achat a été supprimé avec succès.');
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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Le statut de l'achat #{$purchase->reference} a été mis à jour."
                ]);
            }

            return back()->with('success', "Le statut de l'achat #{$purchase->reference} a été mis à jour.");
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Erreur lors du changement de statut : " . $e->getMessage()
                ], 500);
            }
            return back()->with('error', "Erreur lors du changement de statut : " . $e->getMessage());
        }
    }
}
