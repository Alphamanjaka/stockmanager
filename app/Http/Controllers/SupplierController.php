<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\SupplierService;
use App\Services\PurchaseService;

class SupplierController extends Controller
{
    protected $supplierService;
    protected $purchaseService;

    public function __construct(SupplierService $supplierService, PurchaseService $purchaseService)
    {
        $this->supplierService = $supplierService;
        $this->purchaseService = $purchaseService;
    }
    /**
     * Affiche la liste des fournisseurs avec statistiques.
     */
    public function index(Request $request)
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'search' => $request->get('search'),
            'sort' => $request->get('sort'),
            'order' => $request->get('order'),
            'per_page' => $request->get('per_page', 10),
        ];
        // Récupération des fournisseurs via le service
        $suppliers = $this->supplierService->getAllSuppliers($filters);
        // Affichage de la vue
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Enregistrement d'un nouveau fournisseur.
     */
    public function store(StoreSupplierRequest $request)
    {
        $this->supplierService->createSupplier($request->validated());

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur créé avec succès.');
    }

    /**
     * Affiche les détails, l'historique et les stats avancées.
     */
    public function show(Supplier $supplier)
    {
        // On utilise le service pour récupérer les données complexes (statistiques, etc.)
        $details = $this->supplierService->getSupplierDetails($supplier);

        // On utilise le PurchaseService pour récupérer les achats paginés de ce fournisseur
        $purchases = $this->purchaseService->getPurchasesForSupplier($supplier->id, 10);

        // On fusionne toutes les données et on les passe    à la vue
        return view('suppliers.show', array_merge(compact('supplier', 'purchases'), $details));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Mise à jour des informations.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $this->supplierService->updateSupplier($supplier, $validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur mis à jour avec succès.');
    }

    /**
     * Suppression (si aucun achat lié).
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $this->supplierService->deleteSupplier($supplier);

            return redirect()->route('admin.suppliers.index')
                ->with('success', 'Fournisseur supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
