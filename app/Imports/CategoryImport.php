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
    private array $parentsCache = [];

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
            // On s'assure que le nom du parent est bien une chaîne de caractères
            $parentName = strtolower((string) $row['parent']);
            if (!array_key_exists($parentName, $this->parentsCache)) {
                $parent = Category::whereRaw('LOWER(name) = ?', [$parentName])->first();
                $this->parentsCache[$parentName] = $parent ? $parent->id : null;
            }
            $parentId = $this->parentsCache[$parentName];
        }

        // On s'assure que le nom de la catégorie est bien une chaîne de caractères
        $categoryName = (string) $row['name'];

        $category = Category::updateOrCreate(
            ['name' => $categoryName],
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
            'name'        => 'required',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'parent'      => 'nullable',
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
