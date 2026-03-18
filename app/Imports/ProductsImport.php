<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Services\{
    SettingService,
    ProductService
};
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;
    protected $productService;
    protected $settingService;
    private array $categoriesCache = [];

    public function __construct(ProductService $productService, SettingService $settingService)
    {
        $this->productService = $productService;
        // On peut utiliser le service de settings pour récupérer des paramètres globaux si nécessaire
        $this->settingService = $settingService;
    }

    /**
     * @param Row $row
     */
    public function onRow(Row $row)
    {
        $rowData = $row->toArray();

        $categoryId = null;
        if (!empty($rowData['category_id'])) {
            $categoryId = $rowData['category_id'];
        } else if (!empty($rowData['category_name'])) {
            // On s'assure que le nom de la catégorie est bien une chaîne de caractères
            $catName = strtolower((string) $rowData['category_name']);
            if (!array_key_exists($catName, $this->categoriesCache)) {
                $category = Category::whereRaw('LOWER(name) = ?', [$catName])->first();
                $this->categoriesCache[$catName] = $category ? $category->id : null;
            }
            $categoryId = $this->categoriesCache[$catName];
        }

        $productName = (string) $rowData['name'];

        $data = [
            'name'           => $productName,
            'description'    => $rowData['description'] ?? null,
            'price'          => $rowData['price'],
            'quantity_stock' => $rowData['stock'] ?? 0,
            'category_id'    => $categoryId,
            'alert_stock'    => $rowData['alert_stock'] ?? $this->settingService->get('global_alert_threshold') ?? 10,
        ];

        // Vérification si le produit existe pour décider de l'action (Create ou Update)
        $existingProduct = Product::where('name', $productName)->first();

        if ($existingProduct) {
            $this->productService->updateProduct($existingProduct->id, $data);
            $this->updated++;
        } else {
            $this->productService->createProduct($data);
            $this->created++;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category' => 'nullable|string|exists:categories,name',
            'category_id' => 'nullable|integer|exists:categories,id',
        ];
    }

    public function getReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failures' => 0,
            'failure_details' => [],
        ];
    }
}
