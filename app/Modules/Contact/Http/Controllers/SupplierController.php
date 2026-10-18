<?php

namespace App\Modules\Contact\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Modules\Contact\Http\Requests\StoreSupplierRequest;
use App\Modules\Contact\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Modules\Contact\Services\SupplierService;
use App\Modules\Purchase\Services\PurchaseService;
use App\Http\Controllers\Controller;

class SupplierController extends Controller
{
    use ArchivesRecords;

    public function __construct(protected SupplierService $supplierService,protected PurchaseService $purchaseService)
    {
    }
    /**
     * Affiche la liste des fournisseurs avec statistiques.
     */
    public function index(Request $request)
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'sort' => $request->get('sort'),
            'order' => $request->get('order'),
            'per_page' => min(max((int) $request->get('per_page', 10), 1), 100),
        ];
        // Récupération des fournisseurs via le service
        $suppliers = $this->supplierService->getAllSuppliers($filters);
        // Affichage de la vue
        return view('contact::suppliers.index', compact('suppliers'));
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return view('contact::suppliers.create');
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
        return view('contact::suppliers.show', array_merge(compact('supplier', 'purchases'), $details));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Supplier $supplier)
    {
        return view('contact::suppliers.edit', compact('supplier'));
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
            'notes'   => 'nullable|string|max:2000',
        ]);

        $this->supplierService->updateSupplier($supplier, $validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur mis à jour avec succès.');
    }

    /**
     * Suppression définitive d'un fournisseur archivé sans achat : un fournisseur qui a des achats
     * reste archivé (la base refuse), ses achats gardent leur fournisseur.
     */
    public function destroy(Supplier $supplier)
    {
        return $this->deletePermanently($supplier->id, 'admin.suppliers.index');
    }

    protected function archiveTargets(array $ids): Builder
    {
        return Supplier::whereKey($ids);
    }
}