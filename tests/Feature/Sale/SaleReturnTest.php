<?php

namespace Tests\Feature\Sale;

use App\Core\Services\DashboardService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleReturn;
use App\Modules\Sale\Services\SaleReturnService;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Avoirs : retour d'articles ou annulation d'une vente, au back office seulement.
 * Les articles reviennent en stock ; le remboursement compte en négatif le jour de l'avoir.
 */
class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $seller;
    private ProductColor $wax;
    private ProductColor $savon;

    protected function setUp(): void
    {
        parent::setUp();
        // Mercredi : la semaine du graphique commence lundi 19
        $this->travelTo(Carbon::parse('2026-10-21 10:00'));
        $this->admin = User::factory()->backOffice()->create(['name' => 'Rova']);
        $this->seller = User::factory()->frontOffice()->create();

        $this->wax = $this->variant('Wax', 10000, 6000);
        $this->savon = $this->variant('Savon', 2000, 1000);
    }

    private function variant(string $name, float $price, float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 10, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    /** 3 wax + 5 savons = 40 000, remise 4 000 : 36 000 net (chaque article est remboursé 90 % de son prix). */
    private function sale(): Sale
    {
        return app(SaleService::class)->createSale([
            ['product_color_id' => $this->wax->id, 'quantity' => 3],
            ['product_color_id' => $this->savon->id, 'quantity' => 5],
        ], 4000, $this->seller->id);
    }

    private function line(Sale $sale, ProductColor $variant): int
    {
        return $sale->items()->where('product_color_id', $variant->id)->value('id');
    }

    private function returnItems(Sale $sale, array $quantities, string $reason = 'Mauvaise taille')
    {
        return $this->actingAs($this->admin)->post(route('admin.sales.returns.store', $sale->id), [
            'reason' => $reason, 'quantities' => $quantities,
        ]);
    }

    /** @test */
    public function returned_items_go_back_to_stock_with_a_numbered_credit_note()
    {
        $sale = $this->sale();

        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1])
            ->assertRedirect(route('admin.sales.show', $sale->id))
            ->assertSessionHas('success');

        $saleReturn = SaleReturn::firstOrFail();
        $this->assertSame('AVO-2026-000001', $saleReturn->reference);
        $this->assertEquals(9000, $saleReturn->total);
        $this->assertSame($this->admin->id, $saleReturn->user_id);
        $this->assertSame('cash', $saleReturn->refund_method);
        $this->assertSame(8, $this->wax->fresh()->stock);

        $movement = StockMovement::latest('id')->firstOrFail();
        $this->assertSame(['in', 1, 'sale_return', $saleReturn->id], [$movement->type, $movement->quantity, $movement->source_type, $movement->source_id]);
        $this->assertSame("Retour AVO-2026-000001 (vente {$sale->reference})", $movement->reason);
        $this->assertSame(route('admin.sale-returns.show', $saleReturn->id), $movement->sourceUrl());

        // La vente n'est pas modifiée : l'avoir la complète
        $sale = $sale->fresh();
        $this->assertEquals(36000, $sale->total_net);
        $this->assertSame('partially_returned', $sale->returnStatus());
        $this->assertEquals(27000, $sale->netAfterReturns());
    }

    /** @test */
    public function returned_items_re_enter_the_weighted_average_cost_at_their_frozen_cost()
    {
        $sale = $this->sale();
        // Le coût a monté depuis la vente : 7 wax en stock à 8 000
        ProductColor::whereKey($this->wax->id)->update(['price_purchase' => 8000]);

        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1]);

        // (7 × 8 000 + 1 × 6 000) / 8
        $this->assertEquals(7750, $this->wax->fresh()->price_purchase);
    }

    /** @test */
    public function an_item_cannot_be_returned_twice()
    {
        $sale = $this->sale();
        $wax = $this->line($sale, $this->wax);
        $this->returnItems($sale, [$wax => 3]);

        $this->returnItems($sale, [$wax => 1])->assertSessionHas('error');
        $this->returnItems($sale, [$wax => 0])->assertSessionHas('error');

        $this->assertSame(1, SaleReturn::count());
        $this->assertSame(10, $this->wax->fresh()->stock);
    }

    /** @test */
    public function cancelling_returns_everything_left_and_refunds_exactly_the_net_paid()
    {
        $sale = $this->sale();
        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1]);

        $this->actingAs($this->admin)
            ->post(route('admin.sales.returns.store', $sale->id), ['reason' => 'Annulation', 'all' => 1])
            ->assertRedirect(route('admin.sales.show', $sale->id));

        $sale = $sale->fresh();
        $this->assertSame('returned', $sale->returnStatus());
        $this->assertEquals(36000, $sale->refundedTotal());
        $this->assertEquals(0, $sale->netAfterReturns());
        $this->assertSame([10, 10], [$this->wax->fresh()->stock, $this->savon->fresh()->stock]);

        // Plus rien à rendre
        $this->get(route('admin.sales.returns.create', $sale->id))->assertRedirect(route('admin.sales.show', $sale->id));
    }

    /** @test */
    public function rounding_never_refunds_more_or_less_than_the_net_paid()
    {
        // 3 × 1 000, remise 1 : 2 999 net, soit 999,67 par article
        ProductColor::whereKey($this->savon->id)->update(['price' => 1000]);
        $sale = app(SaleService::class)->createSale([['product_color_id' => $this->savon->id, 'quantity' => 3]], 1, $this->seller->id);
        $returns = app(SaleReturnService::class);
        $line = $this->line($sale, $this->savon);

        $this->assertEquals(999.67, $returns->create($sale->id, [$line => 1], 'retour', null, $this->admin->id)->total);
        $this->assertEquals(999.67, $returns->create($sale->id, [$line => 1], 'retour', null, $this->admin->id)->total);
        $this->assertEquals(999.66, $returns->cancel($sale->id, 'annulation', null, $this->admin->id)->total);
        $this->assertEquals(2999, $sale->fresh()->refundedTotal());
    }

    /** @test */
    public function the_refund_counts_against_the_revenue_of_the_return_day()
    {
        // Vente mardi, retour mercredi
        $this->travelTo(Carbon::parse('2026-10-20 15:00'));
        $sale = $this->sale();
        $this->travelTo(Carbon::parse('2026-10-21 10:00'));
        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1]);

        $sales = app(SaleService::class);
        $this->assertEquals(36000, $sales->revenueBetween(Carbon::parse('2026-10-20')->startOfDay(), Carbon::parse('2026-10-20')->endOfDay()));
        $this->assertEquals(-9000, $sales->revenueBetween(today()->startOfDay(), today()->endOfDay()));
        $this->assertEquals(27000, $sales->revenueBetween(now()->startOfWeek(), now()->endOfWeek()));
        $this->assertEquals(-9000, $sales->revenueBetween(today()->startOfDay(), today()->endOfDay(), $this->seller->id), 'Vendeur : retours de ses ventes');

        $statistics = $sales->getSalesStatistics();
        $this->assertEquals(27000, $statistics['total_revenue']);
        $this->assertEquals(9000, $statistics['total_refunds']);

        // Graphique de la semaine : lundi, mardi, mercredi...
        $values = app(DashboardService::class)->getChartData('7days')['values'];
        $this->assertEquals([0, 36000, -9000], array_slice(array_map('floatval', $values), 0, 3));
        $this->assertEquals(27000, array_sum(app(DashboardService::class)->getChartData('1year')['values']));
    }

    /** @test */
    public function the_margin_deducts_the_returned_items()
    {
        $sale = $this->sale();
        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1]);

        // Net 27 000 ; coût 3 × 6 000 + 5 × 1 000 − 6 000 rendus = 17 000
        $sale = app(SaleService::class)->getSaleById($sale->id);
        $this->assertEquals(17000, $sale->costTotal());
        $this->assertEquals(10000, $sale->grossMargin());
        $this->assertEquals(37.0, $sale->marginRate());

        $margin = app(SaleService::class)->getGrossMargin(now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(27000, $margin['revenue']);
        $this->assertEquals(10000, $margin['margin']);
    }

    /** @test */
    public function a_reason_is_required()
    {
        $sale = $this->sale();

        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1], '')->assertSessionHasErrors('reason');
        $this->assertSame(0, SaleReturn::count());
    }

    /** @test */
    public function only_the_back_office_records_returns()
    {
        $sale = $this->sale();
        $this->actingAs($this->seller);

        $this->get(route('admin.sales.returns.create', $sale->id))->assertRedirect(route('dashboard'));
        $this->post(route('admin.sales.returns.store', $sale->id), ['reason' => 'x', 'all' => 1])->assertRedirect(route('dashboard'));
        $this->assertSame(0, SaleReturn::count());
    }

    /** @test */
    public function the_pages_show_the_returns()
    {
        $sale = $this->sale();
        $this->returnItems($sale, [$this->line($sale, $this->wax) => 1]);
        $saleReturn = SaleReturn::firstOrFail();

        $this->get(route('admin.sales.returns.create', $sale->id))->assertOk()->assertSee('Return everything (cancel the sale)');
        $this->get(route('admin.sales.show', $sale->id))
            ->assertOk()
            ->assertSee('Partially returned')
            ->assertSee('AVO-2026-000001')
            ->assertSee('Net after returns');
        $this->get(route('admin.sale-returns.show', $saleReturn->id))
            ->assertOk()
            ->assertSee('Credit note AVO-2026-000001')
            ->assertSee('Mauvaise taille')
            ->assertSee('9,000.00');
        $this->get(route('admin.sales.index'))->assertOk()->assertSee('Partially returned')->assertSee('9,000.00 MGA refunded');

        // Le vendeur voit le retour sur sa vente, sans les coûts
        $this->actingAs($this->seller)->get(route('saler.show', $sale->id))
            ->assertOk()
            ->assertSee('Some items of this sale were returned')
            ->assertDontSee('Cost of items');
    }
}
