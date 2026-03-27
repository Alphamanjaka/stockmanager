<?php

namespace App\Services;

use App\Models\Color;
use App\Services\BaseService;

class ColorService extends BaseService
{
    public function __construct(Color $color)
    {
        parent::__construct($color); // Appel du constructeur parent
    }

    // Vous ne gardez ici que la logique spécifique à Color si nécessaire
    public function getAllColors(array $filters = [], bool $isPaginated = true)
    {
        // Définit un tri par défaut spécifique aux couleurs si non fourni
        $filters['sort'] = $filters['sort'] ?? 'name';
        return $this->getAll($filters, $isPaginated);
    }
}
