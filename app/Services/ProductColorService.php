<?php

namespace App\Services;

use App\Models\ProductColor;
use App\Models\Color;
use App\Services\BaseService;
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


    public function __construct(
        protected ProductColor $productColor,
        protected SaleService $saleService,
        protected ProductService $productService,
        protected ColorService $colorService
    ) {
        parent::__construct($productColor); // Appel du constructeur parent
    }

    /**
     * Orchestre la création d'un produit et de ses variantes de couleurs/stocks.
     *
     * @param array $data Données validées issues du StoreProductRequest
     * @return \App\Models\Product
     * @throws Exception
     */
    public function storeProductWithVariants(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                // 1. Sauvegarde produit via productService
                $product = $this->productService->create($data);

                // Gestion des variantes (Couleurs et Stocks)
                if (!empty($data['colors']) && is_array($data['colors'])) {
                    foreach ($data['colors'] as $index => $colorName) {
                        if (empty($colorName)) continue;

                        // 2. Sauvegarde color si nouvelle via Color (firstOrCreate)
                        $color = Color::firstOrCreate(['name' => $colorName]);

                        // 3. Sauvegarde dans la table productcolor
                        ProductColor::updateOrCreate(
                            ['product_id' => $product->id, 'color_id' => $color->id],
                            [
                                'stock' => $data['stocks'][$index] ?? 0,
                                'price' => $data['prices'][$index] ?? 0,
                                'alert_stock' => $data['alert_stocks'][$index] ?? null
                            ]
                         );
                    }
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
        $query = ProductColor::with(['product.category', 'color']);

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
        return ProductColor::create($data);
    }

    /**
     * Update an existing ProductColor variant.
     */
    public function updateProductColor(int $variantId, array $data): ProductColor
    {
        $productColor = ProductColor::findOrFail($variantId);
        $productColor->update($data);
        return $productColor;
    }

    /**
     * Delete a ProductColor variant.
     */
    public function deleteProductColor(int $variantId): bool
    {
        $query = ProductColor::with(['product.category', 'color']);


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
        return ProductColor::with(['product.category', 'color'])->findOrFail($id);
    }

    // Get all stocks with product and color details

    public function listAllStocks()
    {
        return ProductColor::with(['product', 'color'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    /**
     * Liste les couleurs et stocks pour un produit spécifique
     */
    public function listByProduct(int $productId)
    {
        return ProductColor::with('color')
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
                $sq->where('name', 'like', "%{$search}%");
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
     * Get the most sold product.
     */
    public function getMostSoldProduct()
    {
        return $this->saleService->getMostSoldProduct();
    }

    /**
     * Get the least sold product among those that have been sold.
     */
    public function getLeastSoldProduct()
    {
        return $this->saleService->getLeastSoldProduct();
    }
}
