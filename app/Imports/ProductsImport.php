<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
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
        } else if (!empty($rowData['category'])) {
            $category = Category::where('name', $rowData['category'])->first();
            if ($category) {
                $categoryId = $category->id;
            }
        }

        $data = [
            'name'           => $rowData['name'],
            'description'    => $rowData['description'] ?? null,
            'price'          => $rowData['price'],
            'quantity_stock' => $rowData['stock'] ?? 0,
            'category_id'    => $categoryId,
            'alert_stock'    => $rowData['alert_stock'] ?? 10,
        ];

        // Vérification si le produit existe pour décider de l'action (Create ou Update)
        $existingProduct = Product::where('name', $rowData['name'])->first();

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
