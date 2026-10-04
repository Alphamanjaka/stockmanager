<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Inventory\Http\Requests\SaveCountsRequest;
use App\Modules\Inventory\Http\Requests\StoreInventoryRequest;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected CategoryService $categoryService
    ) {}

    public function index()
    {
        $inventories = $this->inventoryService->getAll();

        return view('inventory::inventories.index', compact('inventories'));
    }

    public function create()
    {
        $categories = $this->categoryService->getAll(['sort' => 'name'], false);

        return view('inventory::inventories.create', compact('categories'));
    }

    public function store(StoreInventoryRequest $request)
    {
        $inventory = $this->inventoryService->create(
            $request->validated('category_id'),
            $request->validated('notes'),
            auth()->id()
        );

        return redirect()->route('admin.inventories.show', $inventory->id)
            ->with('success', "Inventaire {$inventory->reference} ouvert : saisissez les quantités comptées.");
    }

    public function show(Inventory $inventory)
    {
        $inventory = $this->inventoryService->getById($inventory->id);
        $lines = $this->inventoryService->linesFor($inventory);

        return view('inventory::inventories.show', compact('inventory', 'lines'));
    }

    /** Enregistre les comptages (l'inventaire reste en brouillon). */
    public function update(SaveCountsRequest $request, Inventory $inventory)
    {
        try {
            $this->inventoryService->saveCounts($inventory, $request->validated('counts', []));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.inventories.show', $inventory->id)
            ->with('success', 'Comptages enregistrés.');
    }

    /** Valide l'inventaire et ajuste le stock des lignes comptées. */
    public function validateInventory(SaveCountsRequest $request, Inventory $inventory)
    {
        try {
            // Les comptages saisis dans le formulaire sont enregistrés avant la validation
            $this->inventoryService->saveCounts($inventory, $request->validated('counts', []));
            $this->inventoryService->validate($inventory, auth()->id());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.inventories.show', $inventory->id)
            ->with('success', "Inventaire {$inventory->reference} validé : le stock a été ajusté.");
    }

    public function destroy(Inventory $inventory)
    {
        try {
            $this->inventoryService->delete($inventory);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.inventories.index')
            ->with('success', "Inventaire {$inventory->reference} supprimé.");
    }
}
