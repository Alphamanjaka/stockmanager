<?php

namespace App\Core\Http\Controllers;

use App\Core\Exports\ExportRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement d'un export de liste : admin/exports/{liste}/{pdf|xlsx}?{filtres de la page}&ids[]=…
 * Les exports appartiennent aux modules (ExportRegistry) ; le Core ne fait que les servir.
 */
class ExportController extends Controller
{
    public function __invoke(Request $request, ExportRegistry $exports, string $list, string $format): Response
    {
        $export = $exports->make($list) ?? abort(404);

        // Mêmes paramètres que la page de la liste ; ids[] : la sélection des cases à cocher
        $filters = array_filter($request->except(['ids', 'page']), 'is_string');
        $ids = array_values(array_unique(array_map('intval', array_filter(
            (array) $request->input('ids', []),
            fn ($id) => is_scalar($id) && ctype_digit((string) $id)
        ))));

        return $format === 'xlsx'
            ? $export->toExcel($filters, $ids)
            : $export->toPdf($filters, $ids);
    }
}
