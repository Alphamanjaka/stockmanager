<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $categoryId = null;
        if (!empty($row['category_id'])) {
            $categoryId = $row['category_id'];
        } else if (!empty($row['category'])) {
            $category = Category::where('name', $row['category'])->first();
            if ($category) {
                $categoryId = $category->id;
            }
        }
        $product = Product::updateOrCreate(
            ['name' => $row['name']],
            [
                'description'   => $row['description'] ?? null,
                'price'         => $row['price'],
                'quantity_stock' => $row['stock'] ?? 0,
                'category_id'   => $categoryId,
                'alert_stock'   => $row['alert_stock'] ?? 10,
            ]
        );

        if ($product->wasRecentlyCreated) {
            $this->created++;
        } elseif ($product->wasChanged()) {
            $this->updated++;
        }

        return $product;
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
