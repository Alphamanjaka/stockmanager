<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use App\Modules\Sale\Support\PosCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $hair;

    private Category $wigs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hair = Category::factory()->create(['name' => 'Cheveux', 'parent_id' => null]);
        $this->wigs = Category::factory()->create(['name' => 'Postiches', 'parent_id' => $this->hair->id]);
    }

    private function variant(array $attributes = []): ProductColor
    {
        $product = Product::factory()->create(['name' => $attributes['name'] ?? 'Postiche lisse', 'category_id' => $this->wigs->id]);
        unset($attributes['name']);

        return ProductColor::forceCreate($attributes + [
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 4, 'price' => 1000,
        ]);
    }

    private function sell(ProductColor $variant, int $quantity, $date = null): void
    {
        $sale = Sale::factory()->create(['created_at' => $date ?? now()]);
        // created_at n'est pas assignable en masse : forceFill pour dater la ligne
        (new SaleItem)->forceFill([
            'sale_id' => $sale->id, 'product_color_id' => $variant->id, 'quantity' => $quantity,
            'unit_price' => 1000, 'subtotal' => 1000 * $quantity, 'created_at' => $date ?? now(),
        ])->save();
    }

    /** @test */
    public function out_of_stock_variants_are_listed_but_inactive_ones_are_not()
    {
        $inStock = $this->variant();
        $soldOut = $this->variant(['stock' => 0]);
        $inactive = $this->variant(['is_active' => false]);

        $ids = collect((new PosCatalog)->build()['items'])->pluck('stock', 'id');

        $this->assertSame(4, $ids[$inStock->id]);
        $this->assertSame(0, $ids[$soldOut->id]);
        $this->assertFalse($ids->has($inactive->id));
    }

    /** @test */
    public function an_item_belongs_to_its_category_and_every_parent_category()
    {
        $brand = Brand::create(['name' => 'Bella']);
        $variant = $this->variant(['brand_id' => $brand->id, 'sku' => 'POSTICHE-NOIR']);

        $item = collect((new PosCatalog)->build()['items'])->firstWhere('id', $variant->id);

        $this->assertSame([$this->wigs->id, $this->hair->id], $item['categories']);
        $this->assertSame($brand->id, $item['brand']);
        $this->assertSame('POSTICHE-NOIR', $item['sku']);
        $this->assertSame('Postiche lisse', $item['name']);
    }

    /** @test */
    public function best_sellers_are_ranked_on_recent_sales_only()
    {
        $often = $this->variant(['name' => 'Souvent']);
        $sometimes = $this->variant(['name' => 'Parfois']);
        $longAgo = $this->variant(['name' => 'Autrefois']);

        $this->sell($sometimes, 2);
        $this->sell($often, 5);
        $this->sell($longAgo, 50, now()->subYear());

        $this->assertSame([$often->id, $sometimes->id], (new PosCatalog)->build()['top']);
    }

    /** @test */
    public function the_checkout_page_embeds_the_catalog()
    {
        $this->variant(['name' => 'Mèche bouclée']);

        $this->actingAs(User::factory()->frontOffice()->create())
            ->get(route('saler.create'))
            ->assertOk()
            ->assertSee('id="pos-data"', false)
            ->assertSee(json_encode('Mèche bouclée'), false);
    }
}
