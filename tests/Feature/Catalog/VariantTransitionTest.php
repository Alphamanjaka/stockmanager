<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pendant la transition, les écrans actuels (basés sur la couleur) doivent produire
 * des variantes complètes : combinaison d'options, signature et SKU.
 */
class VariantTransitionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->product = Product::factory()->create(['name' => 'Postiche lisse', 'category_id' => Category::factory()->create()->id]);
    }

    /** @test */
    public function a_variant_added_from_the_current_product_page_is_complete()
    {
        $noir = Color::factory()->create(['name' => 'Noir']);

        $this->post(route('admin.products.variants.store', $this->product->id), [
            'color_id' => $noir->id, 'stock' => 3, 'price_purchase' => 500, 'price' => 1000,
        ])->assertSessionHas('success');

        $variant = ProductColor::firstOrFail();
        $this->assertSame(['Couleur' => 'Noir'], $variant->option_values);
        $this->assertSame('couleur=noir', $variant->signature);
        $this->assertSame('POSTICHE-LISSE-NOIR', $variant->sku);
        $this->assertSame('Postiche lisse — Noir', $variant->label);
    }

    /** @test */
    public function a_no_variant_color_gives_a_default_variant_without_options()
    {
        $variant = ProductColor::create([
            'product_id' => $this->product->id, 'color_id' => Color::factory()->create(['name' => 'No Variant'])->id, 'price' => 1000, 'stock' => 0,
        ]);

        $this->assertSame([], $variant->option_values);
        $this->assertSame('', $variant->signature);
        $this->assertSame('POSTICHE-LISSE', $variant->sku);
        $this->assertSame('Postiche lisse', $variant->label);
    }

    /** @test */
    public function changing_the_color_updates_the_options_but_keeps_the_sku()
    {
        $variant = ProductColor::create([
            'product_id' => $this->product->id, 'color_id' => Color::factory()->create(['name' => 'Noir'])->id, 'price' => 1000, 'stock' => 0,
        ]);
        $brun = Color::factory()->create(['name' => 'Brun']);

        $this->put(route('admin.products.variants.update', $variant->id), [
            'color_id' => $brun->id, 'stock' => 0, 'price_purchase' => 500, 'price' => 1000,
        ])->assertSessionHas('success');

        $variant->refresh();
        $this->assertSame(['Couleur' => 'Brun'], $variant->option_values);
        $this->assertSame('couleur=brun', $variant->signature);
        $this->assertSame('POSTICHE-LISSE-NOIR', $variant->sku, 'Le SKU est une référence stable : il ne change pas');
    }

    /** @test */
    public function stock_changes_do_not_touch_the_variant_identity()
    {
        $variant = ProductColor::create([
            'product_id' => $this->product->id, 'color_id' => Color::factory()->create(['name' => 'Noir'])->id, 'price' => 1000, 'stock' => 5,
        ]);

        app(StockService::class)->adjustTo($variant->id, 9, 'Inventaire');
        app(StockService::class)->removeStock($variant->id, 2, 'Vente');

        $variant->refresh();
        $this->assertSame(7, $variant->stock);
        $this->assertSame(['Couleur' => 'Noir'], $variant->option_values);
        $this->assertSame('POSTICHE-LISSE-NOIR', $variant->sku);
    }

    /** @test */
    public function explicit_options_and_brand_are_kept_for_the_next_step()
    {
        $bella = Brand::factory()->create(['name' => 'Bella']);

        $variant = ProductColor::create([
            'product_id' => $this->product->id,
            'color_id' => Color::factory()->create(['name' => 'Noir'])->id,
            'option_values' => ['Couleur' => 'Noir', 'Taille' => '30 cm'],
            'brand_id' => $bella->id,
            'price' => 1000,
            'stock' => 0,
        ]);

        $this->assertSame(['Couleur' => 'Noir', 'Taille' => '30 cm'], $variant->option_values);
        $this->assertSame("couleur=noir;taille=30 cm|brand={$bella->id}", $variant->signature);
        $this->assertSame('POSTICHE-LISSE-NOIR-30CM-BELLA', $variant->sku);
        $this->assertSame('Postiche lisse — Noir / 30 cm — Bella', $variant->fresh()->label);
    }

    /** @test */
    public function the_display_order_follows_the_product_options_not_the_stored_json()
    {
        // PostgreSQL (jsonb) trie les clés : « Taille » serait relue avant « Couleur »
        $this->product->update(['options' => [
            ['name' => 'Taille', 'values' => ['30 cm']],
            ['name' => 'Couleur', 'values' => ['Noir']],
        ]]);

        $variant = ProductColor::create([
            'product_id' => $this->product->id,
            'color_id' => Color::factory()->create(['name' => 'Noir'])->id,
            'option_values' => ['Couleur' => 'Noir', 'Taille' => '30 cm'],
            'price' => 1000,
            'stock' => 0,
        ]);

        $this->assertSame('POSTICHE-LISSE-30CM-NOIR', $variant->sku);
        $this->assertSame('Postiche lisse — 30 cm / Noir', $variant->fresh()->label);
    }

    /** @test */
    public function variants_can_be_filtered_by_option()
    {
        foreach (['Noir', 'Brun'] as $color) {
            ProductColor::create(['product_id' => $this->product->id, 'color_id' => Color::factory()->create(['name' => $color])->id, 'price' => 1, 'stock' => 0]);
        }

        // Fonctionne sur SQLite comme sur PostgreSQL (index GIN en production)
        $this->assertSame(['POSTICHE-LISSE-NOIR'], ProductColor::where('option_values->Couleur', 'Noir')->pluck('sku')->all());
    }
}
