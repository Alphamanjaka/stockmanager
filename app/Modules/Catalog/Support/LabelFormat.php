<?php

namespace App\Modules\Catalog\Support;

/**
 * Formats d'étiquettes (dimensions en mm). Les planches A4 correspondent aux références courantes du commerce
 * (70 × 37, 63,5 × 38,1, 52,5 × 29,7) ; le rouleau sert aux imprimantes thermiques (une étiquette par page).
 */
class LabelFormat
{
    public const DEFAULT = 'a4-3x8';

    /**
     * page : largeur, hauteur ; label : largeur, hauteur ; columns, rows ; margin : haut, gauche ;
     * gap : écart horizontal, vertical ; text : taille du texte en pt.
     */
    private const FORMATS = [
        'a4-3x8' => ['page' => [210, 297], 'label' => [70, 37], 'columns' => 3, 'rows' => 8, 'margin' => [0.5, 0], 'gap' => [0, 0], 'text' => 8],
        'a4-3x7' => ['page' => [210, 297], 'label' => [63.5, 38.1], 'columns' => 3, 'rows' => 7, 'margin' => [15.15, 7.25], 'gap' => [2.5, 0], 'text' => 8],
        'a4-4x10' => ['page' => [210, 297], 'label' => [52.5, 29.7], 'columns' => 4, 'rows' => 10, 'margin' => [0, 0], 'gap' => [0, 0], 'text' => 6.5],
        'roll-50x30' => ['page' => [50, 30], 'label' => [50, 30], 'columns' => 1, 'rows' => 1, 'margin' => [0, 0], 'gap' => [0, 0], 'text' => 7],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::FORMATS);
    }

    public static function get(?string $key): array
    {
        $key = isset(self::FORMATS[$key]) ? $key : self::DEFAULT;

        return ['key' => $key, 'per_page' => self::FORMATS[$key]['columns'] * self::FORMATS[$key]['rows']] + self::FORMATS[$key];
    }

    /** Position (haut, gauche) en mm de la case $index (0 = en haut à gauche, ligne par ligne). */
    public static function position(array $format, int $index): array
    {
        $column = $index % $format['columns'];
        $row = intdiv($index, $format['columns']);

        return [
            'top' => round($format['margin'][0] + $row * ($format['label'][1] + $format['gap'][1]), 2),
            'left' => round($format['margin'][1] + $column * ($format['label'][0] + $format['gap'][0]), 2),
        ];
    }
}
