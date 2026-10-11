<?php

namespace App\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    /**
     * Colonnes autorisées pour le tri ; la première sert de tri par défaut.
     * Le paramètre de tri vient de la requête : une colonne inconnue provoquait une erreur SQL.
     */
    protected array $sortable = ['id'];

    /** Taille de page maximale acceptée depuis la requête. */
    protected int $maxPerPage = 100;

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
        // Tri générique, limité aux colonnes déclarées dans $sortable
        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? $filters['sort'] : $this->sortable[0];
        $order = strtolower((string) ($filters['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $order);

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), $this->maxPerPage);

        return $isPaginated
            ? $query->paginate($perPage)->withQueryString()
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
        // Transaction dédiée : si la base refuse la suppression (clé étrangère), l'échec est annulé
        // proprement au lieu de bloquer une transaction englobante (comportement de PostgreSQL).
        return DB::transaction(fn () => $this->getById($id)->delete());
    }

    public function findOneBy(array $criteria)
    {
        return $this->model->where($criteria)->first();
    }
}
