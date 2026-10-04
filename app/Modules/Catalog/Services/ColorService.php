<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Color;
use App\Core\Services\BaseService;

class ColorService extends BaseService
{
    protected array $sortable = ['id', 'name', 'code', 'created_at'];

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
