<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Modules\Catalog\Http\Requests\StoreColorRequest;
use App\Modules\Catalog\Models\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Catalog\Services\ColorService;
use App\Core\Http\Controllers\BaseResourceController;

class ColorController extends BaseResourceController
{
    use ArchivesRecords;

    public function __construct(ColorService $colorService)
    {
        parent::__construct($colorService);
        // Définir les propriétés spécifiques à la ressource Color
        $this->viewPath = 'catalog::colors';
        $this->routeNamePrefix = 'admin.colors';
        $this->resourceName = 'color';
    }

    /**
     * Enregistre une nouvelle couleur.
     */
    public function store(FormRequest $request)
    {
        // On force la résolution de StoreColorRequest pour déclencher sa validation spécifique
        $validatedRequest = app(StoreColorRequest::class);

        return parent::store($validatedRequest);
    }

    /**
     * Met à jour une couleur existante.
     */
    public function update(FormRequest $request, $id)
    {
        $validatedRequest = app(StoreColorRequest::class);

        return parent::update($validatedRequest, $id);
    }

    /**
     * Suppression définitive d'une couleur archivée et jamais utilisée : une couleur portée par des variantes
     * reste archivée (la base refuse ; avant, ses variantes étaient supprimées avec elle).
     */
    public function destroy($id)
    {
        return $this->deletePermanently((int) $id, 'admin.colors.index');
    }

    protected function archiveTargets(array $ids): Builder
    {
        return Color::whereKey($ids);
    }
}
