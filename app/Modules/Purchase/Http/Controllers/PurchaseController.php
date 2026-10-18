<?php

namespace App\Modules\Purchase\Http\Controllers;

use App\Modules\Purchase\Http\Requests\SavePurchaseRequest;
use App\Modules\Purchase\Http\Resources\PurchaseApiResourceCollection;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Contact\Services\SupplierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

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
        $pdf = Pdf::loadView('purchase::purchases.pdf', compact('purchase'));

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

        return view('purchase::purchases.create_from_shortage', [
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
        // Taille de page bornée : une valeur énorme chargerait toute la table
        $size = min(max((int) $request->get('size', 15), 1), 100);
        $purchases = $this->purchaseService->getPurchasesForApi($params, $size);

        return new PurchaseApiResourceCollection($purchases);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $stats = $this->purchaseService->getPurchaseStatistics();
        $stateCounts = $this->purchaseService->getPurchaseStateCounts();

        return view('purchase::purchases.index', array_merge(
            compact('stateCounts'),
            $stats
        ));
    }

    /**
     * Nouveau bon de commande.
     */
    public function create()
    {
        return view('purchase::purchases.create', $this->formData());
    }

    /**
     * Enregistre un bon de commande, puis applique l'action du bouton : brouillon, commandé ou réceptionné.
     */
    public function store(SavePurchaseRequest $request)
    {
        try {
            $purchase = $this->purchaseService->savePurchaseOrder(null, $request->validated(), $request->action());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', "Erreur lors de l'enregistrement de l'achat : " . $e->getMessage());
        }

        return redirect()->route('admin.purchases.show', $purchase->id)->with('success', $this->savedMessage($purchase, $request->action()));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        return view('purchase::purchases.show', compact('purchase'));
    }

    /**
     * Modification d'un bon de commande : seul un brouillon peut l'être.
     */
    public function edit(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);

        if ($purchase->state !== 'Draft') {
            return redirect()->route('admin.purchases.show', $id)->with('error', 'Seul un achat en brouillon peut être modifié.');
        }

        return view('purchase::purchases.edit', $this->formData($purchase));
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
     * Enregistre les modifications d'un brouillon, puis applique l'action du bouton.
     */
    public function update(SavePurchaseRequest $request, int $id)
    {
        try {
            $purchase = $this->purchaseService->savePurchaseOrder($id, $request->validated(), $request->action());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', "Erreur lors de la mise à jour de l'achat : " . $e->getMessage());
        }

        return redirect()->route('admin.purchases.show', $id)->with('success', $this->savedMessage($purchase, $request->action()));
    }

    /**
     * Derniers prix payés au fournisseur, par variante (JSON) : coût proposé dans le bon de commande.
     */
    public function supplierPrices(int $supplier)
    {
        return response()->json(['prices' => (object) $this->purchaseService->lastUnitPricesForSupplier($supplier)]);
    }

    /**
     * Export the specified purchase to PDF.
     */
    public function exportPdf(int $id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);

        $pdf = Pdf::loadView('purchase::purchases.pdf', compact('purchase'));

        return $pdf->download('achat_' . $purchase->reference . '.pdf');
    }

    /**
     * Update the state of the specified purchase.
     */
    public function updateState(Request $request, int $id)
    {
        $validated = $request->validate([
            'state' => ['required', Rule::in(['Draft', 'Ordered', 'Received'])],
        ]);

        $purchase = $this->purchaseService->getPurchaseById($id);

        try {
            // Le service met à jour le stock lors de la réception et refuse toute modification d'un achat déjà reçu.
            $this->purchaseService->changeState($purchase, $validated['state']);

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

    /**
     * Données du formulaire de bon de commande : catalogue pour la recherche, fournisseurs,
     * ruptures par fournisseur et lignes de départ (achat modifié, ou saisie renvoyée après une erreur).
     */
    private function formData(?Purchase $purchase = null): array
    {
        $catalog = $this->productColorService->getAll([], false)->map(fn ($variant) => [
            'id' => $variant->id,
            'name' => $variant->label,
            'sku' => $variant->sku,
            'stock' => (int) $variant->stock,
            'alert' => (int) $variant->alert_stock,
            // Coût proposé par défaut : prix d'achat de la variante (jamais son prix de vente)
            'cost' => (float) $variant->price_purchase,
        ])->values();

        // Ruptures (stock au seuil d'alerte ou en dessous), rangées par dernier fournisseur connu
        $shortages = $this->purchaseService->getShortageProductsGroupedBySupplier()
            ->filter(fn ($group) => !empty($group['supplier_id']))
            ->mapWithKeys(fn ($group) => [$group['supplier_id'] => collect($group['products'])->map(fn ($product) => [
                'id' => $product->id,
                'quantity' => $product->suggested_quantity,
                'unit_price' => (float) $product->last_unit_price,
            ])->values()]);

        $lines = old('products', $purchase?->items->map(fn ($item) => [
            'product_color_id' => $item->product_color_id,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->all() ?? []);

        return [
            'purchase' => $purchase,
            'suppliers' => $this->supplierService->getAllSuppliers(),
            'formData' => [
                'catalog' => $catalog,
                'shortages' => $shortages,
                'lines' => array_values($lines),
                'pricesUrl' => route('admin.purchases.supplier-prices', ['supplier' => '__SUPPLIER__']),
                'currency' => settings('currency_symbol', 'MGA'),
                'currencyBefore' => settings('currency_position', 'after') === 'before',
            ],
        ];
    }

    /** Message affiché après l'enregistrement, selon l'action choisie. */
    private function savedMessage(Purchase $purchase, string $action): string
    {
        return match ($action) {
            'ordered' => "L'achat {$purchase->reference} est enregistré comme commandé.",
            'received' => "L'achat {$purchase->reference} est réceptionné : le stock a été augmenté.",
            default => "L'achat {$purchase->reference} est enregistré en brouillon. Le stock augmentera à sa réception.",
        };
    }
}
