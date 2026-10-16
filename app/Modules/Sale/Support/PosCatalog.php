<?php

namespace App\Modules\Sale\Support;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Support\Facades\DB;

/**
 * Données de l'écran de caisse, chargées une fois avec la page : la recherche et les filtres
 * (catégorie, marque) se font ensuite dans le navigateur, sans attente entre deux articles.
 *
 * Les variantes en rupture sont incluses (affichées « Rupture ») pour que le caissier puisse
 * renseigner le client ; elles ne peuvent pas être ajoutées au panier.
 */
class PosCatalog
{
    /** Nombre d'articles proposés dans l'onglet « Les plus vendus ». */
    private const TOP_SELLERS = 24;

    /** Période prise en compte pour les meilleures ventes. */
    private const TOP_SELLERS_DAYS = 90;

    public function build(): array
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);
        $parents = $categories->pluck('parent_id', 'id');

        $items = ProductColor::with(['product.category', 'color', 'brand'])
            ->where('is_active', true)
            ->get()
            ->map(fn (ProductColor $variant) => [
                'id' => $variant->id,
                'name' => $variant->product->name ?? '',
                'variant' => $variant->variant_name,
                'sku' => $variant->sku,
                'price' => (float) $variant->price,
                'stock' => (int) $variant->stock,
                'alert' => (int) $variant->alert_stock,
                'brand' => $variant->brand_id,
                'categories' => $this->lineage($variant->product?->category_id, $parents),
            ])
            ->sortBy(fn (array $item) => mb_strtolower("{$item['name']} {$item['variant']}"))
            ->values();

        return [
            'items' => $items,
            'categories' => $categories->map->only(['id', 'name', 'parent_id'])->values(),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'top' => $this->topSellers(),
        ];
    }

    /** Catégorie du produit puis ses parents : filtrer sur une catégorie inclut ses sous-catégories. */
    private function lineage(?int $categoryId, $parents): array
    {
        $lineage = [];
        while ($categoryId !== null && !in_array($categoryId, $lineage, true)) {
            $lineage[] = $categoryId;
            $categoryId = $parents[$categoryId] ?? null;
        }

        return $lineage;
    }

    /** Variantes les plus vendues récemment, de la plus vendue à la moins vendue. */
    private function topSellers(): array
    {
        return SaleItem::select('product_color_id', DB::raw('SUM(quantity) as sold'))
            ->where('created_at', '>=', now()->subDays(self::TOP_SELLERS_DAYS))
            ->groupBy('product_color_id')
            ->orderByDesc('sold')
            ->limit(self::TOP_SELLERS)
            ->pluck('product_color_id')
            ->all();
    }
}
