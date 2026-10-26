<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Services\AbcAnalysisService;
use App\Modules\Sale\Services\SaleReturnService;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Analyse ABC : A pour les premiers 80 % du chiffre d'affaires, B pour les 15 % suivants, C pour le reste
 * (dont les produits en vente sans aucune vente sur la période).
 */
class AbcAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->frontOffice()->create();
        $this->category = Category::factory()->create();
    }

    private function variant(string $name, float $price, int $stock = 100, float $cost = 0): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => $this->category->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    private function sell(ProductColor $variant, int $quantity, float $discount = 0)
    {
        return app(SaleService::class)->createSale([['product_color_id' => $variant->id, 'quantity' => $quantity]], $discount, $this->seller->id);
    }

    private function line(array $analysis, ProductColor $variant): object
    {
        return $analysis['lines']->first(fn ($line) => $line->variant->id === $variant->id);
    }

    /** @test */
    public function products_are_classed_by_their_share_of_revenue()
    {
        $wax = $this->variant('Wax', 1000);       // 70 000 : 70 %
        $savon = $this->variant('Savon', 1000);   // 15 000 : 85 % cumulés
        $huile = $this->variant('Huile', 1000);   // 10 000 : 95 %
        $peigne = $this->variant('Peigne', 1000); //  5 000 : 100 %
        $unsold = $this->variant('Brosse', 1000, stock: 4, cost: 500);

        $this->sell($wax, 70);
        $this->sell($savon, 15);
        $this->sell($huile, 10);
        $this->sell($peigne, 5);

        $analysis = app(AbcAnalysisService::class)->analyse();

        $this->assertEquals(100000, $analysis['revenue']);
        $this->assertSame([$wax->id, $savon->id, $huile->id, $peigne->id, $unsold->id], $analysis['lines']->map(fn ($line) => $line->variant->id)->all());
        // Le savon franchit les 80 % : il est en A
        $this->assertSame(['A', 'A', 'B', 'C', 'C'], $analysis['lines']->pluck('class')->all());
        $this->assertEquals([70, 85, 95, 100], $analysis['lines']->take(4)->pluck('cumulative')->all());

        $this->assertSame(['count' => 2, 'revenue' => 85000.0, 'share' => 85.0, 'stock_value' => 0.0], $analysis['classes']['A']);
        $this->assertSame(1, $analysis['classes']['B']['count']);
        // Le produit sans vente compte en C, avec son stock au coût
        $this->assertSame(2, $analysis['classes']['C']['count']);
        $this->assertEquals(2000, $analysis['classes']['C']['stock_value']);
        $this->assertSame(0, $this->line($analysis, $unsold)->quantity);
    }

    /** @test */
    public function the_discount_and_the_returns_are_deducted_from_revenue()
    {
        $wax = $this->variant('Wax', 1000);
        $sale = $this->sell($wax, 10, discount: 1000); // 10 000 − 1 000 de remise : 9 000

        app(SaleReturnService::class)->create($sale->id, [$sale->items()->value('id') => 2], 'Défaut', userId: $this->seller->id);

        // 2 articles rendus, remboursés 900 chacun (part de remise déduite)
        $line = $this->line(app(AbcAnalysisService::class)->analyse(), $wax);
        $this->assertSame(8, $line->quantity);
        $this->assertEquals(7200, $line->revenue);
    }

    /** @test */
    public function only_the_sales_of_the_period_count()
    {
        $wax = $this->variant('Wax', 1000);
        $this->travelTo(now()->subDays(100));
        $this->sell($wax, 3);
        $this->travelBack();
        $this->sell($wax, 1);

        $abc = app(AbcAnalysisService::class);
        $this->assertEquals(1000, $this->line($abc->analyse(90), $wax)->revenue);
        $this->assertEquals(4000, $this->line($abc->analyse(180), $wax)->revenue);
        // Une période inconnue retombe sur 90 jours
        $this->assertSame(90, $abc->analyse(7)['days']);
    }

    /** @test */
    public function an_archived_product_appears_only_if_it_sold_during_the_period()
    {
        $sold = $this->variant('Ancien wax', 1000);
        $this->sell($sold, 2);
        $unsold = $this->variant('Ancien savon', 1000);
        Product::whereKey([$sold->product_id, $unsold->product_id])->update(['archived_at' => now()]);

        $ids = app(AbcAnalysisService::class)->analyse()['lines']->map(fn ($line) => $line->variant->id);

        $this->assertContains($sold->id, $ids);
        $this->assertNotContains($unsold->id, $ids);
    }

    /** @test */
    public function the_page_lists_a_class_and_exports_it()
    {
        $wax = $this->variant('Wax', 1000);
        $this->sell($wax, 9);
        $brosse = $this->variant('Brosse', 1000);
        $this->sell($brosse, 1);
        $admin = User::factory()->backOffice()->create();

        $this->actingAs($admin)->get(route('admin.sales.abc', ['days' => 30, 'class' => 'A']))
            ->assertOk()
            ->assertSee('Wax')
            ->assertDontSee('Brosse');

        $this->actingAs($admin)->get(route('admin.exports.download', ['list' => 'abc-analysis', 'format' => 'xlsx', 'class' => 'C']))
            ->assertOk()
            ->assertDownload();
        $this->actingAs($admin)->get(route('admin.exports.download', ['list' => 'abc-analysis', 'format' => 'pdf']))
            ->assertOk();
    }

    /** @test */
    public function sellers_cannot_see_the_analysis()
    {
        $this->actingAs($this->seller)->get(route('admin.sales.abc'))->assertRedirect(route('dashboard'));
    }
}
