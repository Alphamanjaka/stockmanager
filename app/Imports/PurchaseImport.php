<?php

namespace App\Imports;

use App\Services\PurchaseService;
use App\Services\SupplierService;
use App\Services\ProductService;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PurchaseImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected PurchaseService $purchaseService;
    protected SupplierService $supplierService;
    protected ProductService $productService;

    private int $created = 0;
    private array $errors = [];

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
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Optimisation N+1 : On récupère toutes les références nécessaires en une seule fois
        $emails = $rows->pluck('email_fournisseur')->unique()->filter();
        // On s'assure que les noms de produits sont bien des chaînes de caractères
        $productNames = $rows->pluck('nom_produit')->map(fn($name) => (string) $name)->unique()->filter();

        // Chargement en mémoire : ['email' => id] et ['name' => id]
        $suppliers = Supplier::whereIn('email', $emails)->pluck('id', 'email');
        $products = Product::whereIn('name', $productNames)->pluck('id', 'name');

        // On groupe les lignes par 'reference_groupe' pour créer un achat avec plusieurs items
        $groupedPurchases = $rows->groupBy('reference_groupe');

        foreach ($groupedPurchases as $reference => $items) {
            $firstItem = $items->first();
            $email = $firstItem['email_fournisseur'];

            // Recherche du fournisseur
            if (!isset($suppliers[$email])) {
                // On pourrait logger une erreur ici si le fournisseur n'existe pas
                continue;
            }
            $supplierId = $suppliers[$email];

            $purchaseItems = [];

            foreach ($items as $item) {
                $productName = (string) $item['nom_produit'];
                if (isset($products[$productName])) {
                    $purchaseItems[] = [
                        'product_id' => $products[$productName],
                        'quantity'   => $item['quantite'],
                        'unit_price' => $item['cout_unitaire'],
                    ];
                }
            }

            if (!empty($purchaseItems)) {
                // Le service gère la transaction, la création de l'achat, des items et la mise à jour du stock
                $this->purchaseService->processPurchase($supplierId, $purchaseItems);
                $this->created++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'reference_groupe'  => 'required|string',
            'email_fournisseur' => 'required|email|exists:suppliers,email',
            'nom_produit'       => 'required|string|exists:products,name',
            'quantite'          => 'required|integer|min:1',
            'cout_unitaire'     => 'required|numeric|min:0',
        ];
    }

    public function getReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => 0, // Les achats sont toujours créés, jamais mis à jour via import pour l'instant
            'failures' => 0,
            'failure_details' => [],
        ];
    }
}
