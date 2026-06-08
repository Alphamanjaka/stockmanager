<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Imports\{ProductsImport, PurchaseImport, ColorImport,};
use App\Imports\CategoryImport;
use App\Imports\SupplierImport;
use App\Services\{ProductService, PurchaseService, SupplierService, ImportService, SettingService, ColorService, ProductColorService};
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;


class ImportController extends Controller
{

    public function __construct(protected ImportService $importService) {}
    public function index(Request $request)
    {
        return view('import.index');
    }
    // Store method for handling imports can be added here
    public function store(StoreImportRequest $request)
    {
        $validated = $request->validated();


        // Optimisation : Instanciation conditionnelle pour éviter de créer des objets inutiles
        $import = match ($validated['type']) {
            'products'   => new ProductsImport(
                app(ProductService::class),
                app(SettingService::class),
                app(CategoryService::class),
                app(ProductColorService::class)
            ),
            'suppliers'  => new SupplierImport(app(SupplierService::class)),
            'categories' => new CategoryImport(app(CategoryService::class)),
            'purchases'  => new PurchaseImport(
                app(PurchaseService::class),
                app(SupplierService::class),
                app(ProductService::class),
                app(ProductColorService::class)
            ),
            'colors' => new ColorImport(app(ColorService::class)),
            default => null
        };

        if (!$import) {
            return back()->with('error', 'Type d\'importation non pris en charge.');
        }

        try {
            $this->importService->import(
                $request->file('file'),
                $import
            );

            if (method_exists($import, 'getReport')) {
                $report = $import->getReport();
                return back()->with('import_report', $report)->with('success', 'Importation terminée avec succès.');
            }

            return back()->with('success', 'Importation réussie !');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $detailedFailures = [];

            foreach ($failures as $failure) {
                $detailedFailures[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(), // Affiche les valeurs de la ligne qui a échoué
                ];
            }

            return back()->with('import_validation_errors', $detailedFailures)
                ->with('error', 'L\'importation a été annulée car des erreurs de validation ont été détectées.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'importation [' . $request->type . '] : ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            // au cas d'une exception générale, on retourne un message d'erreur générique pour éviter de divulguer des informations sensibles
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
    public function downloadTemplate($type)
    {
        // Alignement des en-têtes avec les clés attendues par les classes d'import
        $headers = match ($type) {
            'products'   => ['name', 'price', 'category_id', 'category_name', 'description'],
            'categories' => ['name', 'description', 'parent', 'parent_id'],
            'suppliers'  => ['name', 'email', 'phone', 'address'],
            'purchases' => ['reference_groupe', 'email_fournisseur', 'nom_produit', 'couleur_produit', 'quantite', 'cout_unitaire'],
            'colors' => ['name', 'code'],
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