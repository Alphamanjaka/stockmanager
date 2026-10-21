<?php

namespace Tests\Feature\Stock;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le stock est valorisé au coût d'achat, jamais au prix de vente (qui comprend la marge).
 */
class StockValueEvolutionTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock, float $price, ?float $cost, ?Category $category = null): ProductColor
    {
        // Prix du produit parent volontairement différent : il ne doit pas intervenir
        $product = Product::factory()->create(['price' => 999, 'category_id' => ($category ?? Category::factory()->create())->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    /** @test */
    public function past_values_use_the_same_variant_cost_as_the_current_value()
    {
        $variant = $this->variant(10, 100, 60);

        // Aujourd'hui : 3 unités sorties → 7 en stock
        app(StockService::class)->removeStock($variant->id, 3, 'Vente');

        $evolution = app(StockService::class)->getTotalStockValueEvolution(2);

        $this->assertSame([600.0, 420.0], array_map(fn ($point) => (float) $point['value'], $evolution));
    }

    /** @test */
    public function a_variant_without_cost_counts_for_zero_and_is_reported()
    {
        $this->variant(10, 100, 60);
        $this->variant(5, 100, null);
        $this->variant(0, 100, 0); // pas en stock : sans importance

        $this->assertSame(600.0, (float) last(app(StockService::class)->getTotalStockValueEvolution(1))['value']);
        $this->assertSame(1, app(StockService::class)->countStockWithoutCost());

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.movements.index'))
            ->assertOk()
            ->assertSee('Last 30 days, at purchase cost')
            ->assertSee('1 variant(s) in stock have no purchase price and count for 0.');
    }

    /** @test */
    public function the_category_page_values_all_its_variants_at_cost()
    {
        $tissu = Category::factory()->create();
        $this->variant(10, 100, 60, $tissu);
        $this->variant(4, 50, 20, $tissu);
        $this->variant(100, 100, 60); // autre catégorie

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.categories.show', $tissu->id))
            ->assertOk()
            ->assertSee('680.00');
    }
}
