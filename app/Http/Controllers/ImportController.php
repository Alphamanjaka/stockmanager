<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Http\Request;
use App\Imports\{
    ProductsImport,
    SupplierImport,
    CategoryImport,
    PurchaseImport
};
use App\Services\{
    ProductService,
    PurchaseService,
    SupplierService,
    ImportService,
    SettingService
};


class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }
    public function index(Request $request)
    {
        return view('import.index');
    }
    // Store method for handling imports can be added here
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xls,xlsx',
            'type' => 'required|in:products,suppliers,categories,purchases'
        ]);

        // Optimisation : Instanciation conditionnelle pour éviter de créer des objets inutiles
        $import = match ($request->type) {
            'products'   => new ProductsImport(app(ProductService::class),app(SettingService::class)),
            'suppliers'  => new SupplierImport,
            'categories' => new CategoryImport,
            'purchases'  => new PurchaseImport(
                app(PurchaseService::class),
                app(SupplierService::class),
                app(ProductService::class)
            ),
        };

        try {
            $this->importService->import(
                $request->file('file'),
                $import
            );

            if (method_exists($import, 'getReport')) {
                return back()->with('import_report', $import->getReport());
            }

            return back()->with('success', 'Importation réussie !');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                // Format error message: "Ligne 5: Le nom est requis."
                $errorMessages[] = "Ligne " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            return back()->with('error', "L'importation a été annulée car des erreurs ont été trouvées :<br>" . implode('<br>', $errorMessages));
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
    public function downloadTemplate($type)
    {
        // Alignement des en-têtes avec les clés attendues par les classes d'import
        $headers = match ($type) {
            'products'   => ['name', 'price', 'stock', 'category_id', 'category_name', 'description', 'alert_stock'],
            'categories' => ['name', 'description', 'parent', 'parent_id'],
            'suppliers'  => ['name', 'email', 'phone', 'address'],
            'purchases' => ['reference_groupe', 'email_fournisseur', 'nom_produit', 'quantite', 'cout_unitaire'],
            default => []
        };

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=template_{$type}.csv",
        ]);
    }
}
