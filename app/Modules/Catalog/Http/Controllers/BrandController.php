<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::withCount('variants')
            ->when($request->get('search'), fn ($query, $search) => $query->whereLike('name', "%{$search}%"))
            ->orderBy('name')
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
     * Une marque utilisée n'est pas supprimée : ses variantes perdraient leur marque
     * (et deux variantes pourraient devenir identiques).
     */
    public function destroy(Brand $brand)
    {
        $used = $brand->variants()->count();
        if ($used > 0) {
            return back()->with('error', "Marque utilisée par {$used} variante(s) : retirez-la d'abord de ces variantes.");
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Marque supprimée.');
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
