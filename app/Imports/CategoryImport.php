<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CategoryImport implements ToModel, WithHeadingRow, WithValidation
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
        $parentId = null;

        // 1. Si un ID de parent est fourni directement
        if (!empty($row['parent_id'])) {
            $parentId = $row['parent_id'];
        }
        // 2. Sinon, si un NOM de parent est fourni, on cherche son ID
        elseif (!empty($row['parent'])) {
            $parent = Category::where('name', $row['parent'])->first();
            if ($parent) {
                $parentId = $parent->id;
            }
        }

        $category = Category::updateOrCreate(
            ['name' => $row['name']],
            [
                'description' => $row['description'] ?? null,
                'parent_id'   => $parentId,
            ]
        );

        if ($category->wasRecentlyCreated) {
            $this->created++;
        } elseif ($category->wasChanged()) {
            $this->updated++;
        }

        return $category;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'parent'      => 'nullable|string',
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
