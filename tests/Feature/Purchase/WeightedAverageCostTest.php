<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coût moyen pondéré (CUMP) : chaque réception recalcule le prix d'achat de la variante.
 */
class WeightedAverageCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function variant(int $stock, ?float $cost): ProductColor
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'price' => 5000, 'price_purchase' => $cost,
        ]);
    }

    /** @param list<array{0: ProductColor, 1: int, 2: float}> $lines [variante, quantité, prix unitaire] */
    private function receive(array $lines, float $discount = 0): void
    {
        $service = app(PurchaseService::class);
        $items = array_map(fn ($line) => ['product_color_id' => $line[0]->id, 'quantity' => $line[1], 'unit_price' => $line[2]], $lines);

        $service->changeState($service->processPurchase(null, $items, ['discount' => $discount]), 'Received');
    }

    private function cost(ProductColor $variant): float
    {
        return (float) $variant->fresh()->price_purchase;
    }

    /** @test */
    public function the_first_reception_sets_the_cost()
    {
        $variant = $this->variant(0, 0);

        $this->receive([[$variant, 10, 1000]]);

        $this->assertSame(1000.0, $this->cost($variant));
        $this->assertSame(10, $variant->fresh()->stock);
    }

    /** @test */
    public function a_reception_averages_the_cost_of_the_stock_and_of_the_received_quantity()
    {
        // 4 en stock à 1 000, 6 reçus à 2 000 : (4 000 + 12 000) / 10
        $variant = $this->variant(4, 1000);

        $this->receive([[$variant, 6, 2000]]);
        $this->assertSame(1600.0, $this->cost($variant));

        // 10 à 1 600, 5 reçus à 1 000 : (16 000 + 5 000) / 15
        $this->receive([[$variant, 5, 1000]]);
        $this->assertSame(1400.0, $this->cost($variant));
    }

    /** @test */
    public function the_purchase_discount_is_spread_over_its_lines()
    {
        $wax = $this->variant(0, null);
        $savon = $this->variant(0, null);

        // 10 000 + 30 000 = 40 000, remise 4 000 (10 %) : chaque article coûte 10 % de moins
        $this->receive([[$wax, 10, 1000], [$savon, 10, 3000]], 4000);

        $this->assertSame(900.0, $this->cost($wax));
        $this->assertSame(2700.0, $this->cost($savon));
    }

    /** @test */
    public function without_stock_or_known_cost_the_received_cost_is_taken_as_is()
    {
        $soldOut = $this->variant(0, 1000);
        $noCost = $this->variant(8, null);

        $this->receive([[$soldOut, 5, 1500], [$noCost, 2, 700]]);

        $this->assertSame(1500.0, $this->cost($soldOut));
        $this->assertSame(700.0, $this->cost($noCost));
    }

    /** @test */
    public function a_line_without_price_leaves_the_cost_unchanged()
    {
        $variant = $this->variant(5, 1000);

        $this->receive([[$variant, 5, 0]]);

        $this->assertSame(1000.0, $this->cost($variant));
        $this->assertSame(10, $variant->fresh()->stock);
    }

    /** @test */
    public function only_receptions_change_the_cost()
    {
        $variant = $this->variant(10, 1000);
        $stock = app(StockService::class);

        $stock->correct($variant->id, 'in', 5, 'retour client');
        $stock->adjustTo($variant->id, 20, 'Inventaire INV-1');
        $stock->removeStock($variant->id, 3, 'Vente');

        // Commandé, pas encore reçu
        $service = app(PurchaseService::class);
        $service->changeState($service->processPurchase(null, [['product_color_id' => $variant->id, 'quantity' => 10, 'unit_price' => 3000]]), 'Ordered');

        $this->assertSame(1000.0, $this->cost($variant));
    }

    /** @test */
    public function receiving_from_the_purchase_page_updates_the_cost()
    {
        $variant = $this->variant(10, 1000);
        $service = app(PurchaseService::class);
        $purchase = $service->processPurchase(null, [['product_color_id' => $variant->id, 'quantity' => 10, 'unit_price' => 2000]]);

        $this->patch(route('admin.purchases.updateState', $purchase->id), ['state' => 'Received'])->assertSessionHasNoErrors();

        $this->assertSame(1500.0, $this->cost($variant));
    }
}
