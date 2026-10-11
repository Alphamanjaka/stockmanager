<?php

namespace App\Core\Services;

use Illuminate\Support\Collection;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Stock\Services\StockManagementService;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Contact\Services\CustomerService;

class GlobalSearchService
{
    public function __construct(
        protected ProductColorService   $productColorService,
        protected PurchaseService $purchaseService,
        protected SaleService $saleService,
        protected StockManagementService $stockMovementService,
        protected CustomerService $customerService
    ) {}

    /**
     * Effectue une recherche globale à travers différentes entités.
     *
     * @param string $query La chaîne de recherche.
     * @param int $limit Le nombre maximum de résultats par type d'entité.
     * @return Collection Une collection de résultats formatés.
     */
    public function search(string $query, int $limit = 5): Collection
    {
        $results = collect();

        // Recherche de produits (variantes)
        $productResults = $this->productColorService->searchForGlobalSearch($query, $limit);
        $results = $results->merge($productResults);

        // Recherche d'achats
        $purchaseResults = $this->purchaseService->searchForGlobalSearch($query, $limit);
        $results = $results->merge($purchaseResults);

        // Recherche de ventes (si SaleService existait et avait une méthode searchForGlobalSearch)
        $saleResults = $this->saleService->searchForGlobalSearch($query, $limit);
        $results = $results->merge($saleResults);

        // Recherche de mouvements de stock (si StockMovementService existait)
        $movementResults = $this->stockMovementService->searchForGlobalSearch($query, $limit);
        $results = $results->merge($movementResults);

        // Recherche de clients (nom, téléphone, email)
        $results = $results->merge($this->customerService->searchForGlobalSearch($query, $limit));

        return $results->sortBy('name')->values(); // Tri alphabétique pour une meilleure lisibilité
    }
}