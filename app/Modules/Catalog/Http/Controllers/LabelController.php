<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\LabelService;
use App\Modules\Catalog\Support\LabelFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Étiquettes code-barres : choix des variantes et des quantités, puis planche PDF.
 *
 * La page peut être ouverte avec une sélection : ?product=ID (toutes ses variantes, une étiquette par article en
 * stock) ou ?variants[ID]=quantité (par exemple les quantités reçues d'un achat).
 */
class LabelController extends Controller
{
    public function __construct(private LabelService $labels) {}

    public function create(Request $request)
    {
        $variants = ProductColor::with(['product', 'color', 'brand'])
            ->whereHas('product', fn ($query) => $query->active())
            ->get()
            ->sortBy(fn (ProductColor $variant) => mb_strtolower($variant->label))
            ->values();

        $selection = collect($request->input('variants', []))
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => max(1, (int) $quantity)]);

        if ($request->filled('product')) {
            $selection = $variants->where('product_id', $request->integer('product'))
                ->mapWithKeys(fn (ProductColor $variant) => [$variant->id => max(1, $variant->stock)]);
        }

        // Variantes choisies, dans l'ordre de la sélection ; une variante inconnue est ignorée
        $selected = $selection->map(fn ($quantity, $id) => ['variant' => $variants->firstWhere('id', $id), 'quantity' => min($quantity, LabelService::MAX_LABELS)])
            ->filter(fn ($line) => $line['variant'] !== null)
            ->values();

        return view('catalog::labels.create', compact('variants', 'selected'));
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'integer|min:0|max:'.LabelService::MAX_LABELS,
            'format' => ['nullable', Rule::in(LabelFormat::keys())],
            'skip' => 'nullable|integer|min:0|max:100',
            'price' => 'nullable|boolean',
            'company' => 'nullable|boolean',
        ]);

        try {
            $pdf = $this->labels->pdf($validated['items'], [
                'format' => $validated['format'] ?? null,
                'skip' => $validated['skip'] ?? 0,
                'price' => $request->boolean('price'),
                'company' => $request->boolean('company'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $pdf->stream('etiquettes-'.now()->format('Y-m-d').'.pdf');
    }
}
