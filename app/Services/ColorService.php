<?php

namespace App\Services;

use App\Models\Color;
use Illuminate\Support\Facades\DB;

class ColorService
{
    public function getAllColors(array $filters = [], bool $isPaginated = true)
    {
        $query = Color::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $sort = $filters['sort'] ?? 'name';
        $order = $filters['order'] ?? 'asc';
        $query->orderBy($sort, $order);

        return $isPaginated
            ? $query->paginate($filters['per_page'] ?? 15)->withQueryString()
            : $query->get();
    }

    public function getColorById(int $id): Color
    {
        return Color::findOrFail($id);
    }

    public function create(array $data): Color
    {
        return Color::create($data);
    }

    public function update(int $id, array $data): Color
    {
        return DB::transaction(function () use ($id, $data) {
            $color = Color::findOrFail($id);
            $color->update($data);
            return $color;
        });
    }

    public function delete(int $id): bool
    {
        $color = Color::findOrFail($id);

        // Note: Vous pourriez ajouter une vérification ici pour empêcher
        // la suppression si la couleur est liée à des produits.

        return $color->delete();
    }
}
