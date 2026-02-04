<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductsImport implements ToModel
{
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
        return new Product([
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'price' => $row['price'],
            'stock' => $row['stock'] ?? 0,
            'category_id' => $categoryId,
            'alert_stock' => $row['alert_stock'] ?? 10,
        ]);
    }
}
