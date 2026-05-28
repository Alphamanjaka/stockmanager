<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(protected Model $model) {}

    public function getAll(array $filters = [], bool $isPaginated = true)
    {
        $query = $this->model->query();

        if (!empty($filters['search']) && method_exists($this->model, 'scopeSearch')) {
            // Appel de la méthode de recherche personnalisée du modèle
            $query->search($filters['search']);
        }
        // filtrage par catégorie si le filtre est présent
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        // Ajout de la logique de tri générique
        $sort = $filters['sort'] ?? 'id'; // Tri par défaut par 'id'
        $order = $filters['order'] ?? 'asc';
        $query->orderBy($sort, $order);

        return $isPaginated
            ? $query->paginate($filters['per_page'] ?? 15)->withQueryString()
            : $query->get();
    }

    public function getById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->getById($id);
        $record->update($data);
        return $record;
    }

    public function delete(int $id): bool
    {
        return $this->getById($id)->delete();
    }

    public function findOneBy(array $criteria)
    {
        return $this->model->where($criteria)->first();
    }
}
