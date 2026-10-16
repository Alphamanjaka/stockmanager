<?php

namespace App\Modules\Catalog\Imports;

use App\Core\Services\SettingService;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Catalog\Support\VariantOptions;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

/**
 * Import des produits et de leurs variantes. Une ligne = une variante.
 *
 * Colonnes : name (obligatoire), category, description, brand, price (obligatoire), price_purchase,
 * alert_stock, sku, et autant de colonnes option_<nom> que nécessaire (ex. option_contenance,
 * option_couleur, option_taille). L'ancienne colonne « color » est lue comme l'option « Couleur ».
 *
 * Une ligne met à jour la variante existante (même SKU, sinon même produit + même combinaison + même marque)
 * ou la crée ; réimporter un fichier ne crée donc pas de doublon.
 *
 * Le catalogue ne gère pas le stock : une colonne « stock » est ignorée. Une variante créée démarre à 0 ;
 * le stock vient des achats reçus (import des achats) ou d'un inventaire physique.
 */
class ProductsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private const COLOR_COLUMN = 'color';
    private const OPTION_PREFIX = 'option_';

    private int $created = 0;
    private int $updated = 0;
    private array $products = [];
    private array $categories = [];
    private array $brands = [];

    public function __construct(
        protected ProductService $productService,
        protected SettingService $settingService,
        protected CategoryService $categoryService,
        protected ProductColorService $productColorService
    ) {}

    public function onRow(Row $row)
    {
        try {
            $this->importRow($row->toArray());
        } catch (\Throwable $e) {
            throw new \Exception("Erreur ligne {$row->getIndex()} : " . $e->getMessage());
        }
    }

    private function importRow(array $row): void
    {
        $brandId = $this->brandId($row['brand'] ?? null);
        $sku = trim((string) ($row['sku'] ?? ''));
        $variant = $sku !== '' ? ProductColor::where('sku', $sku)->first() : null;

        if (!$variant) {
            $product = $this->product($row);
            $combination = $this->productColorService->extendOptions($product, $this->optionValues($row));
            $combination = VariantOptions::combination($product->fresh()->options, $combination);

            $variant = ProductColor::where('product_id', $product->id)
                ->where('signature', VariantOptions::signature($combination, $brandId))
                ->first();

            if (!$variant) {
                $this->productColorService->createVariant($product, [
                    'values' => $combination,
                    'brand_id' => $brandId,
                    'price' => $this->number($row['price']),
                    'price_purchase' => $this->number($row['price_purchase'] ?? null) ?? 0,
                    'alert_stock' => $this->integer($row['alert_stock'] ?? null) ?? $this->defaultAlertStock(),
                ]);
                $this->created++;

                return;
            }
        }

        // Variante existante : seules les colonnes renseignées sont mises à jour
        $variant->update(array_filter([
            'price' => $this->number($row['price'] ?? null),
            'price_purchase' => $this->number($row['price_purchase'] ?? null),
            'alert_stock' => $this->integer($row['alert_stock'] ?? null),
            'brand_id' => $brandId,
        ], fn ($value) => $value !== null));
        $this->updated++;
    }

    /** Produit retrouvé par son nom (sans tenir compte de la casse), sinon créé. */
    private function product(array $row): Product
    {
        $name = trim((string) $row['name']);
        $key = mb_strtolower($name);

        // Seul l'identifiant est mémorisé : le produit est relu à chaque ligne (ses options évoluent pendant l'import)
        $this->products[$key] ??= (Product::whereRaw('LOWER(name) = ?', [$key])->first()
            ?? Product::create([
                'name' => $name,
                'category_id' => $this->categoryId($row['category'] ?? null),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'price' => $this->number($row['price'] ?? null) ?? 0,
                'options' => [],
            ]))->id;

        return Product::findOrFail($this->products[$key]);
    }

    /**
     * Combinaison lue dans les colonnes option_<nom> (et l'ancienne colonne « color ») : ["Contenance" => "25 ml"].
     */
    private function optionValues(array $row): array
    {
        $values = [];
        foreach ($row as $column => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if ($column === self::COLOR_COLUMN) {
                $values[VariantOptions::COLOR_OPTION] = $value;
            } elseif (str_starts_with((string) $column, self::OPTION_PREFIX)) {
                $values[Str::ucfirst(str_replace('_', ' ', Str::after($column, self::OPTION_PREFIX)))] = $value;
            }
        }

        // L'ancienne couleur technique « No Variant » signifie : pas d'option
        if (isset($values[VariantOptions::COLOR_OPTION]) && VariantOptions::isNoVariantColor($values[VariantOptions::COLOR_OPTION])) {
            unset($values[VariantOptions::COLOR_OPTION]);
        }

        return $values;
    }

    private function categoryId(?string $name): ?int
    {
        $name = mb_strtolower(trim((string) $name));
        if ($name === '') {
            return null;
        }

        return $this->categories[$name] ??= ($this->categoryService->findOneBy(['name' => $name])
            ?? $this->categoryService->create(['name' => $name]))->id;
    }

    private function brandId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return $this->brands[mb_strtolower($name)] ??= (Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Brand::create(['name' => $name]))->id;
    }

    /** Nombre saisi « 8,400 » ou « 8 400 » → 8400 (même règle que la validation) ; null si vide. */
    private function number($value): ?float
    {
        $value = str_replace([' ', ','], '', trim((string) $value));

        return $value === '' ? null : max(0, (float) $value);
    }

    private function integer($value): ?int
    {
        $number = $this->number($value);

        return $number === null ? null : (int) $number;
    }

    private function defaultAlertStock(): int
    {
        return (int) ($this->settingService->get('global_alert_threshold') ?? 5);
    }

    /**
     * Nettoie les données avant la validation.
     * Convertit par exemple "8,400" en 8400 pour que la règle 'numeric' passe.
     */
    public function prepareForValidation($data, $index)
    {
        foreach (['price', 'price_purchase', 'alert_stock'] as $column) {
            if (isset($data[$column])) {
                $data[$column] = str_replace([' ', ','], '', (string) $data[$column]);
            }
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'price' => 'required|numeric|min:0',
            'price_purchase' => 'nullable|numeric|min:0',
            'alert_stock' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:64',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }

    public function getReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failures' => 0,
            'failure_details' => [],
        ];
    }
}
