<?php

namespace App\Modules\Purchase\Imports;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Contact\Services\SupplierService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PurchaseImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected PurchaseService $purchaseService;
    protected SupplierService $supplierService;
    protected ProductService $productService;
    protected ProductColorService $productColorService;

    private int $created = 0;
    private array $errors = [];

    public function __construct(
        PurchaseService $purchaseService,
        SupplierService $supplierService,
        ProductService $productService,
        ProductColorService $productColorService
    ) {
        $this->purchaseService = $purchaseService;
        $this->supplierService = $supplierService;
        $this->productService = $productService;
        $this->productColorService = $productColorService;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Optimisation N+1 : On récupère toutes les références nécessaires en une seule fois
        $emails = $rows->pluck('email_fournisseur')->unique()->filter();

        // Chargement en mémoire : ['email' => id] et ['name' => id]
        $suppliers = Supplier::whereIn('email', $emails)->pluck('id', 'email');

        // On récupère toutes les variantes existantes pour faire le mapping
        // On crée une clé composite "NomProduit|NomCouleur" pour identifier la variante
        $variants = ProductColor::join('products', 'product_colors.product_id', '=', 'products.id')
            ->join('colors', 'product_colors.color_id', '=', 'colors.id')
            ->select('product_colors.id', 'products.name as p_name', 'colors.name as c_name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [($item->p_name . '|' . $item->c_name) => $item->id];
            });

        // Identification par SKU (prioritaire) : indispensable pour les variantes sans couleur (ex. gel 25 ml / 30 ml)
        $variantsBySku = ProductColor::whereIn('sku', $rows->pluck('sku')->filter()->map(fn ($sku) => trim((string) $sku)))
            ->pluck('id', 'sku');

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
                $sku = trim((string) ($item['sku'] ?? ''));
                $variantKey = ((string) ($item['nom_produit'] ?? '')) . '|' . ((string) ($item['couleur_produit'] ?? ''));
                $variantId = $sku !== '' ? ($variantsBySku[$sku] ?? null) : ($variants[$variantKey] ?? null);

                if ($variantId) {
                    $purchaseItems[] = [
                        'product_color_id' => $variantId,
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
            'sku'               => 'nullable|string|max:64',
            'nom_produit'       => 'required_without:sku|nullable|string',
            'couleur_produit'   => 'required_without:sku|nullable|string',
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
