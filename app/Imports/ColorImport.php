<?php

namespace App\Imports;

use App\Services\ColorService;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ColorImport implements ToModel, WithHeadingRow, WithValidation, WithMapping
{
    public function __construct(private ColorService $colorService) {}

    /**
     * Prépare les données avant la validation et l'insertion.
     */
    public function map($row): array
    {
        // On force la conversion en string et on retire les espaces superflus.
        // Cela permet de gérer les noms comme "1", "2" ou "30" sans erreur de type.
        $row['name'] = isset($row['name']) ? mb_strtolower(trim((string) $row['name'])) : null;

        // Définir une valeur par défaut pour 'code' si elle est manquante ou vide
        if (!isset($row['code']) || empty($row['code'])) {
            $row['code'] = $row['name']; // Valeur par défaut (par exemple, blanc)
        } else {
            $row['code'] = trim((string) $row['code']); // Nettoyer aussi le code s'il est présent
        }

        return $row;
    }

    public function model(array $row)
    {
        $existing = $this->colorService->findOneBy(['name' => $row['name']]);

        if ($existing) {
            return $this->colorService->update($existing->id, $row);
        }

        return $this->colorService->create($row);
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ];
    }
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Le nom de la couleur est obligatoire.',
            'name.max' => 'Le nom de la couleur ne peut pas dépasser 255 caractères.',
        ];
    }
}
