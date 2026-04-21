<?php

namespace App\Services;

use App\Models\ProductColor;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ProductColorService extends BaseService
{
    protected  StockManagementService $StockService;
    protected SaleService $saleService;
    public function __construct(ProductColor $productColor, StockManagementService $StockService, SaleService $saleService)
    {
        parent::__construct($productColor); // Appel du constructeur parent
        $this->StockService = $StockService;
        $this->saleService = $saleService;
    }
    /**
     * Récupère les produits dont le stock est inférieur ou égal au seuil d'alerte.
     */
    public function getShortageProducts()
    {
        return ProductColor::where('quantity_stock', '<=', DB::raw('alert_stock'))
            ->where('alert_stock', '>', 0) // On ne veut pas les produits où l'alerte n'est pas configurée
            ->get();
    }
    public function getAllWithRelations($filters = [])
    {
        $query = ProductColor::with(['product.category', 'color']);

        return $this->applyFilters($query, $filters)->paginate($filters['per_page'] ?? 15);
    }




    /**
     * Get products with available stock
     */
    public function getAvailableProducts()
    {
        return ProductColor::where('quantity_stock', '>', 0)->get();
    }

    /**
     * Get single product by ID
     */
    public function getProductById($id)
    {
        return ProductColor::with(['product.category','color'])->findOrFail($id);
    }

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
