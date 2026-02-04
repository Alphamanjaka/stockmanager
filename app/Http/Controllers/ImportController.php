<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected $importService;
    
    public function __construct(\App\Services\ImportService $importService)
    {
        $this->importService = $importService;
    }
    // Store method for handling imports can be added here
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
            'type' => 'required|in:products,suppliers,categories'
        ]);

        // Mapping entre le type et la classe d'import
        $importMap = [
            'products'   => new \App\Imports\ProductsImport,
            'suppliers'  => new \App\Imports\SupplierImport,
            'categories' => new \App\Imports\CategoryImport,
        ];

        try {

            $this->importService->import(
                $request->file('file'),
                $importMap[$request->type]
            );
            return back()->with('success', 'Importation réussie !');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
