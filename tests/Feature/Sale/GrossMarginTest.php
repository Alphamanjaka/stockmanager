<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Exports\SalesExport;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marge brute : net encaissé moins le coût figé sur les lignes de vente.
 * Affichée au back office seulement (liste et fiche des ventes, tableau de bord, fiche produit).
 */
class GrossMarginTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $seller;
    private ProductColor $wax;
    private ProductColor $savon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create();
        $this->seller = User::factory()->frontOffice()->create();

        // Wax : vendu 10 000, coût 6 000. Savon : vendu 2 000, sans coût.
        $this->wax = $this->variant('Wax', 10000, 6000);
        $this->savon = $this->variant('Savon', 2000, null);
    }

    private function variant(string $name, float $price, ?float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 50, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    /** @param array<int, int> $lines [id de variante => quantité] */
    private function sell(array $lines, float $discount = 0): Sale
    {
        $items = collect($lines)->map(fn ($quantity, $id) => ['product_color_id' => $id, 'quantity' => $quantity])->values()->all();

        return app(SaleService::class)->createSale($items, $discount, $this->seller->id)->load('items');
    }

    /** @test */
    public function the_margin_of_a_sale_is_its_net_total_minus_the_frozen_costs()
    {
        // 3 wax : 30 000 − 1 000 de remise = 29 000 net, pour 18 000 de coût
        $sale = $this->sell([$this->wax->id => 3], 1000);

        $this->assertEquals(18000, $sale->costTotal());
        $this->assertEquals(11000, $sale->grossMargin());
        $this->assertEquals(37.9, $sale->marginRate());
    }

    /** @test */
    public function a_sale_with_an_unknown_cost_has_no_margin()
    {
        $sale = $this->sell([$this->wax->id => 1, $this->savon->id => 1]);

        $this->assertNull($sale->costTotal());
        $this->assertNull($sale->grossMargin());
        $this->assertNull($sale->marginRate());
    }

    /** @test */
    public function the_margin_of_a_period_leaves_out_sales_with_an_unknown_cost()
    {
        $this->sell([$this->wax->id => 2]);                          // 20 000 net, 12 000 de coût
        $this->sell([$this->wax->id => 1], 500);                     // 9 500 net, 6 000 de coût
        $this->sell([$this->wax->id => 1, $this->savon->id => 1]);   // coût inconnu : laissée de côté

        // Le mois dernier : hors période
        $this->travel(-40)->days();
        $this->sell([$this->wax->id => 5]);
        $this->travelBack();

        $margin = app(SaleService::class)->getGrossMargin(now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(29500, $margin['revenue']);
        $this->assertEquals(18000, $margin['cost']);
        $this->assertEquals(11500, $margin['margin']);
        $this->assertEquals(39.0, $margin['rate']);
        $this->assertSame(1, $margin['excluded']);

        // Sans bornes : toutes les ventes
        $this->assertEquals(31500, app(SaleService::class)->getGrossMargin()['margin']);
    }

    /** @test */
    public function no_sale_gives_no_rate()
    {
        $margin = app(SaleService::class)->getGrossMargin();

        $this->assertEquals(0, $margin['margin']);
        $this->assertNull($margin['rate']);
        $this->assertSame(0, $margin['excluded']);
    }

    /** @test */
    public function the_back_office_shows_the_margin_of_sales()
    {
        $known = $this->sell([$this->wax->id => 1]);
        $unknown = $this->sell([$this->savon->id => 1]);
        $this->actingAs($this->admin);

        $this->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('Gross margin')
            ->assertSee('4,000.00')
            ->assertSee('40 %')
            ->assertSee('1 sale(s) without a known cost not counted');

        $this->get(route('admin.sales.show', $known->id))->assertOk()->assertSee('Cost of items')->assertSee('6,000.00');
        $this->get(route('admin.sales.show', $unknown->id))->assertOk()->assertSee('was not known at the time of the sale');

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Gross margin this month')->assertSee('40 % of sales');

        $margins = app(SalesExport::class)->rows([])->map(fn (Sale $sale) => $sale->grossMargin())->all();
        $this->assertEqualsCanonicalizing([4000, null], $margins);
    }

    /** @test */
    public function sellers_never_see_costs_or_margins()
    {
        $sale = $this->sell([$this->wax->id => 1]);
        $this->actingAs($this->seller);

        $this->get(route('saler.show', $sale->id))->assertOk()->assertDontSee('Cost of items')->assertDontSee('6,000.00');
        $this->get(route('saler.index'))->assertOk()->assertDontSee('Gross margin');
    }

    /** @test */
    public function the_product_page_shows_the_unit_margin_of_each_variant()
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.products.show', $this->wax->product_id))
            ->assertOk()
            ->assertSee('4,000.00')
            ->assertSee('40 % of the price');

        $this->assertEquals(4000, $this->wax->unitMargin());
        $this->assertNull($this->savon->unitMargin());
    }
}
