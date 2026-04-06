<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreColorRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\ColorService;

class ColorController extends BaseResourceController
{

    public function __construct(ColorService $colorService)
    {
        parent::__construct($colorService);
        // Définir les propriétés spécifiques à la ressource Color
        $this->viewPath = 'colors';
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
}
