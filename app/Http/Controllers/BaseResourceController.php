<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BaseService;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseResourceController extends Controller
{
    protected BaseService $service;
    protected string $viewPath; // Ex: 'colors'
    protected string $routeNamePrefix; // Ex: 'admin.colors'
    protected string $resourceName; // Ex: 'color' (pour les messages de succès/erreur)

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la liste des ressources.
     */
    public function index(Request $request)
    {
        $filters = [
            'search'   => $request->get('search'),
            'sort'     => $request->get('sort', 'name'),
            'order'    => $request->get('order', 'asc'),
            'per_page' => $request->get('per_page', 15),
        ];

        $items = $this->service->getAll($filters);

        return view("{$this->viewPath}.index", [
            'items' => $items,
        ]);
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view("{$this->viewPath}.create");
    }

    /**
     * Enregistre une nouvelle ressource.
     * Cette méthode doit être surchargée dans les contrôleurs concrets
     * pour typer la FormRequest spécifique.
     */
    public function store(FormRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route("{$this->routeNamePrefix}.index")
            ->with('success', ucfirst($this->resourceName) . ' created successfully.');
    }

    /**
     * Affiche la ressource spécifiée.
     */
    public function show($id)
    {
        $item = $this->service->getById($id);
        return view("{$this->viewPath}.show", compact('item'));
    }

    /**
     * Affiche le formulaire d'édition.
     */
    public function edit($id)
    {
        $item = $this->service->getById($id);
        return view("{$this->viewPath}.edit", compact('item'));
    }

    /**
     * Met à jour la ressource spécifiée.
     * Cette méthode doit être surchargée dans les contrôleurs concrets
     * pour typer la FormRequest spécifique.
     */
    public function update(FormRequest $request, $id)
    {
        $this->service->update($id, $request->validated());

        return redirect()->route("{$this->routeNamePrefix}.index")
            ->with('success', ucfirst($this->resourceName) . ' updated successfully.');
    }

    /**
     * Supprime la ressource spécifiée.
     */
    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return redirect()->route("{$this->routeNamePrefix}.index")
                ->with('success', ucfirst($this->resourceName) . ' deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting ' . $this->resourceName . ': ' . $e->getMessage());
        }
    }
}
