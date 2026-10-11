<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Events\VariantStockDeclared;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Support\VariantOptions;
use App\Core\Services\BaseService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Service dédié à la gestion des couleurs de produits et de leurs stocks associés.
 * Ce service orchestre les interactions entre les modèles ProductColor, Color, Product,
 * ainsi que les services liés à la gestion des stocks et des ventes.
 */
class ProductColorService extends BaseService
{
    protected array $sortable = ['id', 'stock', 'price', 'price_purchase', 'alert_stock', 'created_at'];



    public function __construct(
        protected ProductColor $productColor,
        protected ProductService $productService,
        protected ColorService $colorService
    ) {
        parent::__construct($productColor); // Appel du constructeur parent
    }

    /**
     * Crée un produit, ses options et les variantes retenues dans la grille.
     *
     * @param array $data Données validées issues du StoreProductRequest
     * @return \App\Modules\Catalog\Models\Product
     * @throws Exception
     */
    public function storeProductWithVariants(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                $options = VariantOptions::normalizeOptions($data['options'] ?? []);

                $product = Product::create([
                    'name' => trim($data['name']),
                    'category_id' => $data['category_id'],
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'] ?? 0,
                    'options' => $options,
                    'specs' => $this->normalizeSpecs($data['specs'] ?? []),
                ]);

                foreach ($data['variants'] as $variantData) {
                    if (empty($variantData['enabled'])) {
                        continue;
                    }

                    $optionValues = VariantOptions::combination($options, $variantData['values'] ?? []);

                    $variant = ProductColor::create([
                        'product_id' => $product->id,
                        'option_values' => $optionValues,
                        // Transition : color_id rempli pour que l'ancienne version du code reste utilisable
                        'color_id' => VariantOptions::legacyColorId($optionValues),
                        'brand_id' => $variantData['brand_id'] ?? null,
                        'price' => $variantData['price'] ?? $data['price'] ?? 0,
                        'price_purchase' => $variantData['price_purchase'] ?? 0,
                        'alert_stock' => $variantData['alert_stock'] ?? $this->defaultAlertStock(),
                    ]);

                    // Stock initial : enregistré comme mouvement par le module Stock
                    VariantStockDeclared::dispatch($variant, (int) ($variantData['stock'] ?? 0), 'Stock initial (création du produit)');
                }

                return $product;
            } catch (Exception $e) {
                Log::error("Erreur critique dans ProductColorService@storeProductWithVariants: " . $e->getMessage(), [
                    'payload' => $data,
                    'trace' => $e->getTraceAsString()
                ]);
                throw new Exception("Impossible de créer le produit. Vérifiez les données des variantes." . $e->getMessage());
            }
        });
    }

    /** Spécifications libres : [["key" => "Origine", "value" => "Chine"]] → ["Origine" => "Chine"] (lignes vides ignorées). */
    private function normalizeSpecs(array $rows): array
    {
        $specs = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }

        return $specs;
    }

    /** Seuil d'alerte appliqué quand il n'est pas saisi (la colonne n'accepte pas NULL). */
    private function defaultAlertStock(): int
    {
        return (int) settings('global_alert_threshold', 5);
    }

    /**
     * Récupère les produits dont le stock est inférieur ou égal au seuil d'alerte.
     */
    public function getShortageProducts()
    {
        return ProductColor::where('stock', '<=', DB::raw('alert_stock'))
            ->where('alert_stock', '>', 0) // On ne veut pas les produits où l'alerte n'est pas configurée
            ->get();
    }
    public function getAllWithRelations($filters = [])
    {
        $query = ProductColor::with(['product.category', 'color', 'brand']);

        return $this->applyFilters($query, $filters)->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ajoute une variante à un produit existant (fiche produit).
     *
     * $data['values'] : une valeur par option du produit ; une valeur nouvelle (ex. « 35 ml »)
     * est ajoutée aux options du produit.
     *
     * @throws Exception si le produit n'a pas d'option (une seule variante possible) ou si la variante existe déjà
     */
    public function createVariant(Product $product, array $data): ProductColor
    {
        return DB::transaction(function () use ($product, $data) {
            $product = $this->ensureOptions(Product::lockForUpdate()->findOrFail($product->id));
            $options = $product->options;

            if ($options === [] && $product->productColors()->exists()) {
                throw new Exception("Ce produit n'a pas d'option : définissez d'abord une option (ex. Contenance) pour créer d'autres variantes.");
            }

            $combination = $this->requireCombination($options, $data['values'] ?? []);
            $brandId = !empty($data['brand_id']) ? (int) $data['brand_id'] : null;
            $this->ensureUnique($product->id, $combination, $brandId);
            $this->appendNewValues($product, $combination);

            $variant = ProductColor::create([
                'product_id' => $product->id,
                'option_values' => $combination,
                // Transition : color_id rempli pour que l'ancienne version du code reste utilisable
                'color_id' => VariantOptions::legacyColorId($combination),
                'brand_id' => $brandId,
                'price' => $data['price'] ?? $product->price ?? 0,
                'price_purchase' => $data['price_purchase'] ?? 0,
                'alert_stock' => $data['alert_stock'] ?? $this->defaultAlertStock(),
            ]);

            // Stock initial : enregistré comme mouvement par le module Stock
            VariantStockDeclared::dispatch($variant, (int) ($data['stock'] ?? 0), 'Stock initial (nouvelle variante)');

            return $variant;
        });
    }

    /**
     * Modifie une variante : combinaison, marque, prix, seuil et stock (corrigé par un mouvement).
     * Le SKU ne change pas : c'est une référence stable.
     *
     * @throws Exception si la nouvelle combinaison existe déjà sur une autre variante du produit
     */
    public function updateVariant(int $variantId, array $data): ProductColor
    {
        return DB::transaction(function () use ($variantId, $data) {
            $variant = ProductColor::with('product')->findOrFail($variantId);
            $product = $this->ensureOptions(Product::lockForUpdate()->findOrFail($variant->product_id));

            $combination = $this->requireCombination($product->options, $data['values'] ?? []);
            $brandId = !empty($data['brand_id']) ? (int) $data['brand_id'] : null;
            $this->ensureUnique($product->id, $combination, $brandId, $variant->id);
            $this->appendNewValues($product, $combination);

            $variant->update([
                'option_values' => $combination,
                'color_id' => VariantOptions::legacyColorId($combination),
                'brand_id' => $brandId,
                'price' => $data['price'],
                'price_purchase' => $data['price_purchase'] ?? 0,
                'alert_stock' => $data['alert_stock'] ?? $this->defaultAlertStock(),
            ]);

            if (array_key_exists('stock', $data) && $data['stock'] !== null) {
                VariantStockDeclared::dispatch($variant, (int) $data['stock'], 'Correction manuelle (fiche produit)');
            }

            return $variant;
        });
    }

    /**
     * Met à jour les options et spécifications d'un produit.
     * Une option ou une valeur utilisée par une variante ne peut pas être retirée.
     *
     * @throws Exception si une valeur utilisée disparaît
     */
    public function updateOptions(Product $product, array $optionRows, array $specRows): Product
    {
        return DB::transaction(function () use ($product, $optionRows, $specRows) {
            // Une option sans valeur n'a pas de sens sur un produit : elle est ignorée
            $options = array_values(array_filter(VariantOptions::normalizeOptions($optionRows), fn ($option) => $option['values'] !== []));

            foreach ($product->productColors as $variant) {
                foreach ($variant->option_values ?? [] as $name => $value) {
                    $option = collect($options)->first(fn ($candidate) => mb_strtolower($candidate['name']) === mb_strtolower($name));
                    $kept = $option && in_array(mb_strtolower($value), array_map('mb_strtolower', $option['values']), true);
                    if (!$kept) {
                        throw new Exception("« {$name} : {$value} » est utilisé par la variante {$variant->sku} : modifiez ou supprimez d'abord cette variante.");
                    }
                }
            }

            $product->update(['options' => $options, 'specs' => $this->normalizeSpecs($specRows)]);

            return $product;
        });
    }

    /**
     * Étend les options d'un produit avec une combinaison (import) : axe nouveau ajouté,
     * valeur nouvelle ajoutée à son axe. Les noms d'axes sont rapprochés sans tenir compte de la casse ni des accents.
     *
     * @return array la combinaison avec les noms d'axes du produit
     */
    public function extendOptions(Product $product, array $values): array
    {
        $product = $this->ensureOptions($product);
        $options = $product->options;
        $combination = [];

        foreach ($values as $name => $value) {
            $index = collect($options)->search(fn ($option) => Str::slug($option['name']) === Str::slug($name));
            if ($index === false) {
                $options[] = ['name' => $name, 'values' => [$value]];
                $combination[$name] = $value;
                continue;
            }

            $combination[$options[$index]['name']] = $value;
            if (!in_array(mb_strtolower($value), array_map('mb_strtolower', $options[$index]['values']), true)) {
                $options[$index]['values'][] = $value;
            }
        }

        if ($options !== $product->options) {
            $product->update(['options' => $options]);
        }

        return $combination;
    }

    /**
     * Produit ancien dont les options n'ont pas encore été remplies (catalog:migrate-variants) :
     * elles sont reconstituées à partir des combinaisons de ses variantes, pour ne jamais les écraser.
     */
    private function ensureOptions(Product $product): Product
    {
        if ($product->options === null) {
            $product->update(['options' => VariantOptions::optionsFromVariants($product->productColors()->pluck('option_values')->filter())]);
        }

        return $product;
    }

    /** Combinaison complète (une valeur non vide par option du produit), sinon exception. */
    private function requireCombination(array $options, array $values): array
    {
        $combination = VariantOptions::combination($options, $values);
        foreach ($combination as $name => $value) {
            if ($value === '') {
                throw new Exception("Indiquez une valeur pour « {$name} ».");
            }
        }

        return $combination;
    }

    /** Une même combinaison (même marque) ne peut exister qu'une fois par produit. */
    private function ensureUnique(int $productId, array $combination, ?int $brandId, ?int $ignoreVariantId = null): void
    {
        $exists = ProductColor::where('product_id', $productId)
            ->where('signature', VariantOptions::signature($combination, $brandId))
            ->when($ignoreVariantId, fn ($query) => $query->whereKeyNot($ignoreVariantId))
            ->exists();

        if ($exists) {
            throw new Exception('Cette variante existe déjà pour ce produit.');
        }
    }

    /** Ajoute aux options du produit les valeurs nouvelles d'une combinaison (ex. « 35 ml »). */
    private function appendNewValues(Product $product, array $combination): void
    {
        $options = $product->options ?? [];
        foreach ($options as &$option) {
            $value = $combination[$option['name']] ?? null;
            $known = array_map('mb_strtolower', $option['values']);
            if ($value !== null && !in_array(mb_strtolower($value), $known, true)) {
                $option['values'][] = $value;
            }
        }
        unset($option);

        if ($options !== ($product->options ?? [])) {
            $product->update(['options' => $options]);
        }
    }

    /**
     * Delete a ProductColor variant.
     */
    public function deleteProductColor(int $variantId): bool
    {
        try {
            // Transaction dédiée : un refus de la base (variante déjà vendue) est annulé proprement
            return DB::transaction(fn () => ProductColor::findOrFail($variantId)->delete());
        } catch (QueryException $e) {
            // SQLSTATE 23xxx : la variante est référencée par des ventes
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw new Exception('Cette variante a déjà été vendue : elle ne peut pas être supprimée.');
            }
            throw $e;
        }
    }




    /**
     * Get products with available stock
     */
    public function getAvailableProducts()
    {
        return ProductColor::where('stock', '>', 0)->get();
    }

    /**
     * Get single product by ID
     */
    public function getProductById(int $id)
    {
        return ProductColor::with(['product.category', 'color', 'brand'])->findOrFail($id);
    }

    // Get all stocks with product and color details

    public function listAllStocks()
    {
        return ProductColor::with(['product', 'color', 'brand'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    /**
     * Liste les couleurs et stocks pour un produit spécifique
     */
    public function listByProduct(int $productId)
    {
        return ProductColor::with(['color', 'brand', 'product'])
            ->where('product_id', $productId)
            ->get();
    }

    /**
     * Récupère un mapping des variantes pour l'import (NomProduit|NomCouleur => ID)
     */
    public function getVariantsMapping()
    {
        return ProductColor::join('products', 'product_colors.product_id', '=', 'products.id')
            ->join('colors', 'product_colors.color_id', '=', 'colors.id')
            ->select('product_colors.id', 'products.name as p_name', 'colors.name as c_name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [($item->p_name . '|' . $item->c_name) => $item->id];
            });
    }


    // filter method for products list
    protected function applyFilters($query, $filters)
    {
        // On réutilise ta logique de colonnes autorisées
        $sortableColumns = ['stock', 'created_at'];

        $sort = in_array($filters['sort'] ?? '', $sortableColumns) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->whereHas('product', function ($sq) use ($search) {
                $sq->whereLike('name', "%{$search}%");
            });
        })
            ->when($filters['color'] ?? null, function ($q, $colorId) {
                $q->where('color_id', $colorId);
            })
            ->when($filters['category'] ?? null, function ($q, $category) {
                $q->whereHas('product', function ($sq) use ($category) {
                    $sq->where('category_id', $category);
                });
            });

        return $query
            ->orderBy($sort, $order);
    }

    /**
     * Effectue une recherche légère sur les variantes de produits pour la fonctionnalité de recherche globale.
     *
     * @param string $query La chaîne de recherche.
     * @param int $limit Le nombre maximum de résultats.
     * @return \Illuminate\Support\Collection
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        return ProductColor::with(['product', 'color', 'brand'])
            ->whereHas('product', fn($q) => $q->whereLike('name', "%{$query}%"))
            ->orWhereHas('color', fn($q) => $q->whereLike('name', "%{$query}%"))
            ->limit($limit)
            ->get()
            ->map(fn(ProductColor $productColor) => [
                'type' => 'product',
                'id' => $productColor->id,
                'name' => "{$productColor->toString()}",
                'url' => route('admin.products.show', $productColor->product_id),
            ]);
    }
}