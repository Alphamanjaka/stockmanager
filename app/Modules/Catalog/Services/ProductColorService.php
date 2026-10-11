<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Events\VariantStockDeclared;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Support\VariantOptions;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Create a new ProductColor variant.
     */
    public function createProductColor(array $data): ProductColor
    {
        // Ensure uniqueness of color for a given product
        if (ProductColor::where('product_id', $data['product_id'])->where('color_id', $data['color_id'])->exists()) {
            throw new Exception("Cette couleur est déjà associée à ce produit.");
        }

        return DB::transaction(function () use ($data) {
            $stock = (int) ($data['stock'] ?? 0);
            unset($data['stock']);
            $data['alert_stock'] ??= $this->defaultAlertStock();

            $productColor = ProductColor::create($data);
            // Stock initial : enregistré comme mouvement par le module Stock
            VariantStockDeclared::dispatch($productColor, $stock, 'Stock initial (nouvelle variante)');

            return $productColor;
        });
    }

    /**
     * Update an existing ProductColor variant.
     * Un changement de stock saisi ici est enregistré comme mouvement de correction.
     */
    public function updateProductColor(int $variantId, array $data): ProductColor
    {
        $productColor = ProductColor::findOrFail($variantId);

        // Une couleur ne peut apparaître qu'une fois par produit
        if (isset($data['color_id']) && (int) $data['color_id'] !== $productColor->color_id
            && ProductColor::where('product_id', $productColor->product_id)->where('color_id', $data['color_id'])->exists()) {
            throw new Exception("Cette couleur est déjà associée à ce produit.");
        }

        return DB::transaction(function () use ($productColor, $data) {
            $stock = array_key_exists('stock', $data) ? (int) $data['stock'] : null;
            unset($data['stock']);
            if (array_key_exists('alert_stock', $data)) {
                $data['alert_stock'] ??= $this->defaultAlertStock();
            }

            $productColor->update($data);
            if ($stock !== null) {
                VariantStockDeclared::dispatch($productColor, $stock, 'Correction manuelle (fiche produit)');
            }

            return $productColor;
        });
    }

    /**
     * Delete a ProductColor variant.
     */
    public function deleteProductColor(int $variantId): bool
    {
        $query = ProductColor::with(['product.category', 'color', 'brand']);


        return $query->findOrFail($variantId)->delete();
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