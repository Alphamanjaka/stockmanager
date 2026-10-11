<?php

namespace Tests\Feature\Stock;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockValueEvolutionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function past_values_use_the_same_variant_price_as_the_current_value()
    {
        // Prix du produit parent volontairement différent : il ne doit pas intervenir
        $product = Product::factory()->create(['price' => 999, 'category_id' => Category::factory()->create()->id]);
        $variant = ProductColor::create([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 10, 'price' => 100,
        ]);

        // Aujourd'hui : 3 unités sorties → 7 en stock
        app(StockService::class)->removeStock($variant->id, 3, 'Vente');

        $evolution = app(StockService::class)->getTotalStockValueEvolution(2);

        $this->assertSame([1000.0, 700.0], array_map(fn ($point) => (float) $point['value'], $evolution));
    }
}
