<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseApiResourceCollection;
use App\Services\ProductColorService;
use App\Services\PurchaseService;
use App\Services\SupplierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{

    public function __construct(
        protected PurchaseService $purchaseService,
        protected SupplierService $supplierService,
        protected ProductColorService $productColorService
    ) {}
    /**
     * Génère et affiche le PDF du bon de commande dans le navigateur.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function previewPdf(int $id)
    {
        // Récupération de l'achat avec les relations nécessaires pour la vue PDF
        $purchase = $this->purchaseService->getPurchaseById($id);

        // Génération du PDF en utilisant la vue 'purchases.pdf'
        // Note : Cela nécessite le package barryvdh/laravel-dompdf
        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'));

        // stream() permet d'afficher le PDF dans le navigateur
        // download() l'aurait forcé en téléchargement
        return $pdf->stream("bon_commande_{$purchase->reference}.pdf");
    }


    /**
     * Affiche la page de création de commandes à partir des ruptures de stock.
     * Récupère les produits en rupture de stock groupés par fournisseur pour faciliter la création de commandes.
     * @return \Illuminate\View\View
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromShortage(Request $request)
    {
        // Transformation et validation initiale des données soumises
        $itemsToOrder = collect($request->input('items', []))
            ->filter(fn($item) => isset($item['selected']))
            ->map(fn($item, $id) => [
                'product_color_id' => $item['product_id'] ?? $id,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'supplier_id' => !empty($item['supplier_id']) ? (int) $item['supplier_id'] : null,
            ])
            ->filter(fn($item) => $item['quantity'] > 0)
            ->values()
            ->all();

        if (empty($itemsToOrder)) {
            return redirect()->back()->with('warning', 'Aucun produit valide n\'a été sélectionné.');
        }

        try {
            $createdPurchases = $this->purchaseService->createPurchasesFromShortage($itemsToOrder);

            return redirect()->route('admin.purchases.index')
                ->with('success', count($createdPurchases) . ' commande(s) d\'achat créée(s) avec succès.');
        } catch (\Exception $e) {
            Log::error('Échec de la création des commandes depuis la rupture de stock: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création : ' . $e->getMessage());
        }
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
        $productsVariant = $this->productColorService->getAll([], false);
        $suppliers = $this->supplierService->getAllSuppliers();
        return view('purchases.create', compact('productsVariant', 'suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $data = $request->validated();

        try {
            $purchase = $this->purchaseService->processPurchase(
                $data['supplier_id'] ?? null,
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
    public function show(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        $products = $this->productColorService->getAll([], false);
        $suppliers = $this->supplierService->getAllSuppliers();
        return view('purchases.edit', compact('purchase', 'products', 'suppliers'));
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->purchaseService->deletePurchase($id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'L\'achat a été supprimé avec succès.']);
            }

            return redirect()->route('admin.purchases.index')->with('success', 'L\'achat a été supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error("Purchase Deletion Error: " . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Une erreur est survenue lors de la suppression.'], 500);
            }
            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $this->purchaseService->updatePurchase($id, $validated);
            return redirect()->route('admin.purchases.show', $id)->with('success', 'L\'achat a été mis à jour.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de l\'achat : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Export the specified purchase to PDF.
     */
    public function exportPdf(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'));

        return $pdf->download('achat_' . $purchase->reference . '.pdf');
    }

    /**
     * Update the state of the specified purchase.
     */
    public function updateState(Request $request, int $id)
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