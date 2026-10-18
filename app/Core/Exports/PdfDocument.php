<?php

namespace App\Core\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Création de tous les PDF de l'application (exports de liste, factures, bons de commande) à partir
 * d'une vue qui étend layouts.pdf : format A4, police intégrée en partie seulement (fichier ~10 fois
 * plus léger), « Page x / y » dans le pied de chaque page.
 *
 *     return PdfDocument::load('sale::sales.pdf', compact('sale'))->download("invoice_{$sale->reference}.pdf");
 */
class PdfDocument
{
    public static function load(string $view, array $data = [], string $orientation = 'portrait'): DomPdf
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->setOption('isFontSubsettingEnabled', true);

        self::numberPages($pdf);

        return $pdf;
    }

    /**
     * Numéros de page en bas à droite, dans le pied du gabarit (layouts.pdf).
     * Écrits après le rendu : le CSS de dompdf ne connaît pas le nombre total de pages (counter(pages)).
     */
    private static function numberPages(DomPdf $pdf): void
    {
        // Rendu par l'enveloppe Laravel : download() et stream() ne relancent pas un rendu qui effacerait les numéros
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans');
        $size = 7;
        $text = __('documents.page', ['page' => '{PAGE_NUM}', 'pages' => '{PAGE_COUNT}']);
        $width = $metrics->getTextWidth(str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], '000', $text), $font, $size);

        // Marges du gabarit : 12 mm à droite, pied à ~10 mm du bas (1 mm = 2,835 pt)
        $canvas->page_text($canvas->get_width() - 34 - $width, $canvas->get_height() - 30, $text, $font, $size, [0.52, 0.57, 0.64]);
    }
}
