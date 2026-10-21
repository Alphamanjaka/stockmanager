<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le coût d'achat est figé sur chaque ligne de vente, comme le prix : base du calcul de la marge.
 */
class SaleUnitCostTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->frontOffice()->create();
    }

    private function variant(?float $cost, float $price = 10000): ProductColor
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 20, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    private function sell(ProductColor $variant, int $quantity = 2): SaleItem
    {
        $sale = app(SaleService::class)->createSale([['product_color_id' => $variant->id, 'quantity' => $quantity]], 0, $this->seller->id);

        return $sale->items()->firstOrFail();
    }

    /** @test */
    public function the_purchase_cost_is_frozen_on_the_sale_line()
    {
        $variant = $this->variant(6000);
        $item = $this->sell($variant);

        // Le coût de la variante change ensuite : la ligne de vente garde le coût du jour de la vente
        ProductColor::whereKey($variant->id)->update(['price_purchase' => 7500]);

        $this->assertEquals(6000, $item->fresh()->unit_cost);
        $this->assertEquals(10000, $item->fresh()->unit_price);
    }

    /** @test */
    public function an_unknown_cost_is_left_empty()
    {
        $this->assertNull($this->sell($this->variant(null))->unit_cost);
        $this->assertNull($this->sell($this->variant(0))->unit_cost, 'Un prix d\'achat à 0 veut dire « non renseigné »');
    }

    /** @test */
    public function the_migration_estimates_the_cost_of_past_sales_from_the_current_purchase_price()
    {
        $known = $this->variant(6000);
        $unknown = $this->variant(0);
        $sale = Sale::factory()->create(['user_id' => $this->seller->id]);
        foreach ([$known, $unknown] as $variant) {
            SaleItem::create(['sale_id' => $sale->id, 'product_color_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000]);
        }

        // Base d'avant la migration : pas de colonne unit_cost
        $migration = require base_path('app/Modules/Sale/Database/Migrations/2026_10_22_100000_add_unit_cost_to_sale_items.php');
        $migration->down();
        $migration->up();

        $costs = DB::table('sale_items')->pluck('unit_cost', 'product_color_id');
        $this->assertEquals(6000, $costs[$known->id]);
        $this->assertNull($costs[$unknown->id]);
    }
}
