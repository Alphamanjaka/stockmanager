<?php

namespace App\Modules\Catalog\Support;

use Picqer\Barcode\Renderers\PngRenderer;
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Types\TypeEan13;
use Picqer\Barcode\Types\TypeEan8;
use Picqer\Barcode\Types\TypeUpcA;

/**
 * Codes-barres des variantes : celui du fabricant (EAN-13, EAN-8, UPC-A, saisi ou importé), ou à défaut un
 * code EAN-13 interne attribué à la création de la variante.
 *
 * Code interne : préfixe 20 à 29 (réservé par GS1 à l'usage en magasin, jamais attribué à un fabricant),
 * identifiant de la variante sur 10 chiffres, puis la clé de contrôle : 2000000000428 pour la variante 42.
 */
class Barcode
{
    /** Longueur maximale d'un code saisi (colonne product_colors.barcode). */
    public const MAX_LENGTH = 32;

    /** Code EAN-13 interne d'une variante ; $prefix (20 à 29) change si le code est déjà pris. */
    public static function internal(int $variantId, int $prefix = 20): string
    {
        $body = $prefix.str_pad((string) $variantId, 10, '0', STR_PAD_LEFT);

        return $body.self::checkDigit($body);
    }

    /** Clé de contrôle GTIN (EAN-8, UPC-A, EAN-13) des chiffres qui la précèdent. */
    public static function checkDigit(string $digits): int
    {
        $sum = 0;
        // Poids 3 et 1 en alternance, en partant du chiffre le plus à droite
        foreach (array_reverse(str_split($digits)) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 === 0 ? 3 : 1);
        }

        return (10 - $sum % 10) % 10;
    }

    /** Un code de 8, 12 ou 13 chiffres est un GTIN (EAN-8, UPC-A, EAN-13) : sa clé de contrôle doit être juste. */
    public static function isGtin(string $code): bool
    {
        return (bool) preg_match('/^(\d{8}|\d{12}|\d{13})$/', $code);
    }

    /** Faux seulement pour un GTIN dont la clé de contrôle est fausse (faute de frappe). */
    public static function hasValidCheckDigit(string $code): bool
    {
        return ! self::isGtin($code) || self::checkDigit(substr($code, 0, -1)) === (int) substr($code, -1);
    }

    /** Symbologie imprimée : celle du GTIN, sinon Code 128 (lettres, tirets…). */
    public static function symbology(string $code): string
    {
        return match (self::isGtin($code) && self::hasValidCheckDigit($code) ? strlen($code) : 0) {
            13 => 'EAN-13',
            12 => 'UPC-A',
            8 => 'EAN-8',
            default => 'Code 128',
        };
    }

    /** Image PNG du code-barres (data URI), pour les étiquettes PDF. */
    public static function png(string $code, int $height = 60): string
    {
        $type = match (self::symbology($code)) {
            'EAN-13' => new TypeEan13,
            'UPC-A' => new TypeUpcA,
            'EAN-8' => new TypeEan8,
            default => new TypeCode128,
        };
        $barcode = $type->getBarcode($code);
        // 3 pixels par module : traits nets une fois réduits à la taille de l'étiquette
        $png = (new PngRenderer)->render($barcode, $barcode->getWidth() * 3, $height);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
