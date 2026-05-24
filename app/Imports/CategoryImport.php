<?php

namespace App\Imports;

use App\Services\CategoryService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CategoryImport implements OnEachRow, WithHeadingRow, WithValidation
{

    private int $created = 0;
    private int $updated = 0;
    private array $parentsCache = [];

    public function __construct(
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
            $parentId = null;

            // 1. Si un ID de parent est fourni directement
            if (!empty($rowData['parent_id'])) {
                $parentId = $rowData['parent_id'];
            }
            // 2. Sinon, si un NOM de parent est fourni, on cherche son ID
            elseif (!empty($rowData['parent'])) {
                // On s'assure que le nom du parent est bien une chaîne de caractères
                $parentName = mb_strtolower(trim((string) $rowData['parent']));
                if (!array_key_exists($parentName, $this->parentsCache)) {
                    $parent = $this->categoryService->findOneBy(['name' => $parentName]);
                    $this->parentsCache[$parentName] = $parent ? $parent->id : null;
                }
                $parentId = $this->parentsCache[$parentName];
            }

            // On s'assure que le nom du parent est bien une chaîne de caractères
            $categoryName = mb_strtolower(trim((string) $rowData['name']));
            $data = [
                'name' => $categoryName,
                'description' => $rowData['description'] ?? null,
                'parent_id' => $parentId,
            ];

            // On vérifie si la catégorie existe déjà pour décider de l'action
            $existingCategory = $this->categoryService->findOneBy(['name' => $categoryName]);

            if ($existingCategory) {
                $this->categoryService->update($existingCategory->id, $data);
                $this->updated++;
            } else {
                $this->categoryService->create($data);
                $this->created++;
            }
        } catch (\Throwable $e) {
            throw new \Exception("Erreur ligne {$rowIndex} : " . $e->getMessage());
        }
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
