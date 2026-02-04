<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GenericImport implements ToModel, WithHeadingRow, WithValidation
{
    protected string $modelClass;
    protected array $mapping;
    protected array $rules;

    public function __construct(string $modelClass, array $mapping, array $rules = [])
    {
        $this->modelClass = $modelClass; // Ex: Product::class
        $this->mapping = $mapping;       // Ex: ['name' => 'nom_csv']
        $this->rules = $rules;           // Règles de validation
    }

    public function model(array $row)
    {
        $data = [];
        foreach ($this->mapping as $modelKey => $csvKey) {
            $data[$modelKey] = $row[$csvKey] ?? null;
        }

        return new $this->modelClass($data);
    }

    public function rules(): array
    {
        return $this->rules;
    }
}