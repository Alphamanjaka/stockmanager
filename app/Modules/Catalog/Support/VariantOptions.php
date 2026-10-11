<?php

namespace App\Modules\Catalog\Support;

use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Support\Str;

/**
 * Règles des variantes flexibles : combinaison d'options, signature (anti-doublon), SKU et libellé.
 *
 * Utilisé par le modèle ProductColor (remplissage automatique pendant la transition)
 * et par la commande catalog:migrate-variants.
 */
class VariantOptions
{
    /** Nom de l'option qui reprend l'ancienne couleur obligatoire. */
    public const COLOR_OPTION = 'Couleur';

    /** Couleurs « techniques » utilisées jusqu'ici pour les produits sans déclinaison. */
    private const NO_VARIANT_COLORS = ['no variant', 'sans variante'];

    /**
     * Combinaison déduite de l'ancienne couleur : ["Couleur" => "Rouge"],
     * ou [] (aucune option) pour une couleur « sans variante ».
     */
    public static function fromColor(?Color $color): array
    {
        if (!$color || self::isNoVariantColor($color->name)) {
            return [];
        }

        return [self::COLOR_OPTION => trim($color->name)];
    }

    public static function isNoVariantColor(?string $name): bool
    {
        return in_array(mb_strtolower(trim((string) $name)), self::NO_VARIANT_COLORS, true);
    }

    /**
     * Forme canonique, indépendante de l'ordre et de la casse : "couleur=noir;taille=30 cm|brand=3".
     * Deux variantes d'un même produit avec la même signature sont des doublons.
     */
    public static function signature(array $optionValues, ?int $brandId = null): string
    {
        $pairs = [];
        foreach ($optionValues as $name => $value) {
            $pairs[mb_strtolower(trim((string) $name))] = mb_strtolower(trim((string) $value));
        }
        ksort($pairs);

        $signature = implode(';', array_map(fn ($name, $value) => "{$name}={$value}", array_keys($pairs), $pairs));

        return $brandId ? "{$signature}|brand={$brandId}" : $signature;
    }

    /**
     * SKU lisible et unique : PRODUIT-VALEUR1-VALEUR2[-MARQUE],
     * ex. GEL-DESHYDRATANT-25ML, POSTICHE-LISSE-NOIR-30CM-BELLA.
     * Un suffixe -2, -3… est ajouté si la référence existe déjà (en base ou dans $reserved).
     */
    public static function generateSku(
        string $productName,
        array $optionValues,
        ?string $brandName = null,
        ?int $ignoreVariantId = null,
        array $reserved = [],
        ?array $productOptions = null
    ): string {
        $parts = [self::skuPart($productName, 24, '-')];
        foreach (self::orderedValues($optionValues, $productOptions) as $value) {
            $parts[] = self::skuPart((string) $value, 12);
        }
        if ($brandName) {
            $parts[] = self::skuPart($brandName, 12);
        }

        // 58 caractères au plus pour garder la place d'un suffixe dans la colonne (64)
        $base = rtrim(Str::limit(implode('-', array_filter($parts)), 58, ''), '-') ?: 'ARTICLE';

        $sku = $base;
        for ($i = 2; self::skuTaken($sku, $ignoreVariantId, $reserved); $i++) {
            $sku = "{$base}-{$i}";
        }

        return $sku;
    }

    /**
     * Valeurs dans l'ordre d'affichage. PostgreSQL (jsonb) ne conserve pas l'ordre des clés :
     * l'ordre vient donc des options du produit (choisi par l'utilisateur), puis, à défaut,
     * de l'ordre alphabétique des noms d'options — identique sur toutes les bases.
     */
    public static function orderedValues(array $optionValues, ?array $productOptions = null): array
    {
        $ordered = [];
        foreach ($productOptions ?? [] as $option) {
            $name = $option['name'] ?? null;
            if ($name !== null && array_key_exists($name, $optionValues)) {
                $ordered[$name] = $optionValues[$name];
            }
        }

        $rest = array_diff_key($optionValues, $ordered);
        ksort($rest, SORT_NATURAL | SORT_FLAG_CASE);

        return $ordered + $rest;
    }

    /** Libellé affiché : "Postiche lisse — Noir / 30 cm — Bella". */
    public static function label(string $productName, array $optionValues, ?string $brandName = null, ?array $productOptions = null): string
    {
        $description = self::describe($optionValues, $brandName, $productOptions);

        return $description === '' ? $productName : "{$productName} — {$description}";
    }

    /** Déclinaison seule, sans le nom du produit : "Noir / 30 cm — Bella" ('' pour une variante par défaut sans marque). */
    public static function describe(array $optionValues, ?string $brandName = null, ?array $productOptions = null): string
    {
        $values = array_filter(
            array_map(fn ($value) => trim((string) $value), self::orderedValues($optionValues, $productOptions)),
            fn ($value) => $value !== ''
        );

        return implode(' — ', array_filter([implode(' / ', $values), (string) $brandName]));
    }

    /**
     * Options d'un produit déduites des combinaisons de ses variantes (ordre de première apparition) :
     * [["name" => "Couleur", "values" => ["Rouge", "Bleu"]]].
     */
    public static function optionsFromVariants(iterable $optionValuesList): array
    {
        $options = [];
        foreach ($optionValuesList as $optionValues) {
            foreach ((array) $optionValues as $name => $value) {
                $options[$name] ??= [];
                if (!in_array($value, $options[$name], true)) {
                    $options[$name][] = $value;
                }
            }
        }

        return array_map(fn ($name, $values) => ['name' => $name, 'values' => $values], array_keys($options), $options);
    }

    /** Segment de SKU : majuscules ASCII sans accents ni espaces ("25 ml" → "25ML", "Gel déshydratant" → "GEL-DESHYDRATANT"). */
    private static function skuPart(string $text, int $maxLength, string $separator = ''): string
    {
        return rtrim(Str::limit(Str::upper(Str::slug($text, $separator)), $maxLength, ''), '-');
    }

    private static function skuTaken(string $sku, ?int $ignoreVariantId, array $reserved): bool
    {
        return in_array($sku, $reserved, true)
            || ProductColor::where('sku', $sku)
                ->when($ignoreVariantId, fn ($query) => $query->whereKeyNot($ignoreVariantId))
                ->exists();
    }
}
