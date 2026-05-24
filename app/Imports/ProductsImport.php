<?php

namespace App\Imports;

use App\Services\{
    SettingService,
    ProductService,
    CategoryService
};
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;
    private array $categoriesCache = [];

    public function __construct(
        protected ProductService $productService,
        protected SettingService $settingService,
        protected CategoryService $categoryService
    ) {}

    /**
     * @param Row $row
     */
    public function onRow(Row $row)
    {
        $rowData = $row->toArray();
        $rowIndex = $row->getIndex();

        try {
            $categoryId = null;
            if (!empty($rowData['category_id'])) {
                $categoryId = $rowData['category_id'];
            } else if (!empty($rowData['category_name'])) {
                // On s'assure que le nom de la catégorie est bien une chaîne de caractères
                $catName = mb_strtolower(trim((string) $rowData['category_name']));
                if (!array_key_exists($catName, $this->categoriesCache)) {
                    $category = $this->categoryService->findOneBy(['name' => $catName]);
                    $this->categoriesCache[$catName] = $category ? $category->id : null;
                }
                $categoryId = $this->categoriesCache[$catName];
            }

            $productName = mb_strtolower(trim((string) $rowData['name']));

            $data = [
                'name'           => $productName,
                'description'    => $rowData['description'] ?? null,
                'price'          => $rowData['price'],
                'quantity_stock' => $rowData['stock'] ?? 0,
                'category_id'    => $categoryId,
                'alert_stock'    => $rowData['alert_stock'] ?? $this->settingService->get('global_alert_threshold') ?? 10,
            ];

            // Vérification si le produit existe pour décider de l'action (Create ou Update)
            $existingProduct = $this->productService->findOneBy(['name' => $productName]);

            if ($existingProduct) {
                $this->productService->update($existingProduct->id, $data);
                $this->updated++;
            } else {
                $this->productService->create($data);
                $this->created++;
            }
        } catch (\Throwable $e) {
            throw new \Exception("Erreur ligne {$rowIndex} : " . $e->getMessage());
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
