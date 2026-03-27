<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreColorRequest;
use App\Services\ColorService;
use Illuminate\Http\Request;
use App\Models\Color;

class ColorController extends Controller
{
    protected $colorService;

    public function __construct(ColorService $colorService)
    {
        $this->colorService = $colorService;
    }

    /**
     * Affiche la liste des couleurs.
     */
    public function index(Request $request)
    {
        $filters = [
            'search'   => $request->get('search'),
            'sort'     => $request->get('sort', 'name'),
            'order'    => $request->get('order', 'asc'),
            'per_page' => $request->get('per_page', 15),
        ];

        $colors = $this->colorService->getAllColors($filters);

        return view('colors.index', compact('colors'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('colors.create');
    }

    /**
     * Enregistre une nouvelle couleur.
     */
    public function store(StoreColorRequest $request)
    {
        $this->colorService->create($request->validated());

        return redirect()->route('admin.colors.index')
            ->with('success', 'La couleur a été ajoutée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition.
     */
    public function edit(Color $color)
    {
        return view('colors.edit', compact('color'));
    }

    /**
     * Met à jour une couleur existante.
     */
    public function update(StoreColorRequest $request, Color $color)
    {
        $this->colorService->update($color->id, $request->validated());

        return redirect()->route('admin.colors.index')
            ->with('success', 'La couleur a été mise à jour avec succès.');
    }

    /**
     * Supprime une couleur de la base de données.
     */
    public function destroy(Color $color)
    {
        try {
            $this->colorService->delete($color->id);

            return redirect()->route('admin.colors.index')
                ->with('success', 'La couleur a été supprimée.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}