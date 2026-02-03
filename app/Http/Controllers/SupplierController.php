<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\SupplierService;
use App\Http\Requests\StoreSupplierRequest;

class SupplierController extends Controller
{
    protected $supplierService;
    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'sort' => $request->get('sort', 'name'),
            'order' => $request->get('order', 'asc'),
            'search' => $request->get('search'),
            'per_page' => 10,
        ];

        $suppliers = $this->supplierService->getAllSuppliers($filters);
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // process to show form for creating a supplier
        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        // validate request
        $validatedData = $request->validated();
        try {
            $this->supplierService->createSupplier($validatedData);
            return redirect()->route('admin.suppliers.index')
                ->with('success', 'Fournisseur ajouté avec succès.');
        } catch (\Exception $th) {
            return back()->withInput()
                ->with('error', "Erreur lors de l'ajout : " . $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
