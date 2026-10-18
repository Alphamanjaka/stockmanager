<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use ArchivesRecords;

    public function index(Request $request)
    {
        $brands = Brand::forList($request->only(['search', 'status']))
            ->paginate(20)
            ->withQueryString();

        return view('catalog::brands.index', compact('brands'));
    }

    public function create()
    {
        return view('catalog::brands.create');
    }

    public function store(Request $request)
    {
        Brand::create($this->validated($request));

        return redirect()->route('admin.brands.index')->with('success', 'Marque créée.');
    }

    public function edit(Brand $brand)
    {
        return view('catalog::brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($this->validated($request, $brand));

        return redirect()->route('admin.brands.index')->with('success', 'Marque mise à jour.');
    }

    /**
     * Suppression définitive d'une marque archivée et jamais utilisée : une marque portée par des variantes
     * reste archivée (la base refuse), sinon ces variantes perdraient leur marque.
     */
    public function destroy(Brand $brand)
    {
        return $this->deletePermanently($brand->id, 'admin.brands.index');
    }

    protected function archiveTargets(array $ids): Builder
    {
        return Brand::whereKey($ids);
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand?->id)],
        ], [
            'name.required' => 'Le nom de la marque est requis.',
            'name.unique' => 'Cette marque existe déjà.',
        ]);
    }
}
