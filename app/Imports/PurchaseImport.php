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
    protected $purchaseService;
    protected $supplierService;
    protected $productService;

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
        // On groupe les lignes par 'reference_groupe' pour créer un achat avec plusieurs items
        $groupedPurchases = $rows->groupBy('reference_groupe');

        foreach ($groupedPurchases as $reference => $items) {
            $firstItem = $items->first();

            // Recherche du fournisseur
            $supplier = Supplier::where('email', $firstItem['email_fournisseur'])->first();

            if (!$supplier) {
                // On pourrait logger une erreur ici si le fournisseur n'existe pas
                continue;
            }

            $purchaseItems = [];

            foreach ($items as $item) {
                $product = Product::where('name', $item['nom_produit'])->first();

                if ($product) {
                    $purchaseItems[] = [
                        'product_id' => $product->id,
                        'quantity'   => $item['quantite'],
                        'unit_price' => $item['cout_unitaire'],
                    ];
                }
            }

            if (!empty($purchaseItems)) {
                // Le service gère la transaction, la création de l'achat, des items et la mise à jour du stock
                $this->purchaseService->processPurchase($supplier->id, $purchaseItems);
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
