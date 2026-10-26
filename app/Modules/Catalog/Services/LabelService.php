<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Support\Barcode;
use App\Modules\Catalog\Support\LabelFormat;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Collection;

/**
 * Étiquettes code-barres : nom, prix, code-barres de chaque variante, en planche A4 ou sur rouleau.
 */
class LabelService
{
    /** Au-delà, dompdf devient trop lent pour une seule requête. */
    public const MAX_LABELS = 1000;

    /**
     * @param  array<int, int>  $quantities  [id de variante => nombre d'étiquettes]
     * @param  array{format?: ?string, skip?: int, price?: bool, company?: bool}  $options
     *                                                                                      skip : cases déjà utilisées en haut de la première planche
     */
    public function pdf(array $quantities, array $options = []): DomPdf
    {
        $format = LabelFormat::get($options['format'] ?? null);
        $labels = $this->labels($quantities);

        if ($labels->isEmpty()) {
            throw new \InvalidArgumentException('Aucune étiquette à imprimer.');
        }
        if ($labels->count() > self::MAX_LABELS) {
            throw new \InvalidArgumentException(__('At most :max labels per print.', ['max' => self::MAX_LABELS]));
        }

        // Cases vides en tête de la première planche (planche entamée)
        $skip = $format['per_page'] > 1 ? max(0, min((int) ($options['skip'] ?? 0), $format['per_page'] - 1)) : 0;
        $cells = collect(array_fill(0, $skip, null))->concat($labels);

        $pdf = Pdf::loadView('catalog::labels.pdf', [
            'format' => $format,
            'pages' => $cells->chunk($format['per_page'])->map->values(),
            'showPrice' => $options['price'] ?? true,
            'company' => ($options['company'] ?? false) ? settings('company_name') : null,
            'currency' => settings('currency_symbol', 'MGA'),
        ]);

        // Taille de page en points (1 mm = 72 / 25,4 pt)
        return $pdf->setPaper([0, 0, $format['page'][0] * 72 / 25.4, $format['page'][1] * 72 / 25.4]);
    }

    /** Une entrée par étiquette, dans l'ordre demandé ; l'image d'un code n'est calculée qu'une fois. */
    private function labels(array $quantities): Collection
    {
        $quantities = collect($quantities)->map(fn ($quantity) => (int) $quantity)->filter(fn ($quantity) => $quantity > 0);
        $variants = ProductColor::with(['product', 'color', 'brand'])->findMany($quantities->keys())->keyBy('id');

        return $quantities->flatMap(function (int $quantity, $id) use ($variants) {
            $variant = $variants->get($id);
            if (! $variant?->barcode) {
                return [];
            }

            $label = (object) [
                'name' => $variant->product->name ?? '',
                'variant' => $variant->variant_name,
                'price' => (float) $variant->price,
                'code' => $variant->barcode,
                'image' => Barcode::png($variant->barcode),
            ];

            return array_fill(0, min($quantity, self::MAX_LABELS + 1), $label);
        })->values();
    }
}
