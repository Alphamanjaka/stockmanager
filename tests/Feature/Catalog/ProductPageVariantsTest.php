<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche produit : options, ajout et modification de variantes par leurs options et leur marque.
 */
class ProductPageVariantsTest extends TestCase
{
    use RefreshDatabase;

    private Product $gel;
    private ProductColor $ml25;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());

        $this->gel = Product::factory()->create([
            'name' => 'Gel déshydratant',
            'category_id' => Category::factory()->create()->id,
            'options' => [['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]],
        ]);
        $this->ml25 = ProductColor::create([
            'product_id' => $this->gel->id, 'color_id' => Color::factory()->create(['name' => 'Sans variante'])->id,
            'option_values' => ['Contenance' => '25 ml'], 'price' => 8000, 'stock' => 10,
        ]);
    }

    private function addVariant(array $data)
    {
        return $this->post(route('admin.products.variants.store', $this->gel->id), $data + ['price' => 9000, 'stock' => 0]);
    }

    /** @test */
    public function a_variant_with_a_new_value_extends_the_product_options()
    {
        $this->addVariant(['values' => ['Contenance' => '35 ml'], 'stock' => 6])->assertSessionHas('success');

        $variant = ProductColor::where('sku', 'GEL-DESHYDRATANT-35ML')->firstOrFail();
        $this->assertSame(['Contenance' => '35 ml'], $variant->option_values);
        $this->assertSame(6, $variant->stock);
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $variant->id, 'type' => 'in', 'quantity' => 6]);
        $this->assertSame([['name' => 'Contenance', 'values' => ['25 ml', '30 ml', '35 ml']]], $this->gel->fresh()->options);
    }

    /** @test */
    public function duplicates_and_incomplete_variants_are_refused()
    {
        $this->addVariant(['values' => ['Contenance' => '25 ml']])->assertSessionHas('error');
        $this->addVariant(['values' => ['Contenance' => ' 25 ML ']])->assertSessionHas('error');
        $this->addVariant(['values' => []])->assertSessionHas('error');

        // Même contenance mais autre marque : c'est une autre variante
        $bella = Brand::factory()->create(['name' => 'Bella']);
        $this->addVariant(['values' => ['Contenance' => '25 ml'], 'brand_id' => $bella->id])->assertSessionHas('success');

        $this->assertSame(2, $this->gel->productColors()->count());
    }

    /** @test */
    public function a_product_without_options_keeps_a_single_variant()
    {
        $comb = Product::factory()->create(['name' => 'Peigne', 'category_id' => $this->gel->category_id, 'options' => []]);
        $this->post(route('admin.products.variants.store', $comb->id), ['price' => 2000, 'stock' => 1])->assertSessionHas('success');
        $this->post(route('admin.products.variants.store', $comb->id), ['price' => 2000, 'stock' => 1])->assertSessionHas('error');

        $this->assertSame(1, $comb->productColors()->count());
    }

    /** @test */
    public function a_variant_can_be_edited_without_changing_its_sku()
    {
        $bella = Brand::factory()->create(['name' => 'Bella']);

        $this->put(route('admin.products.variants.update', $this->ml25->id), [
            'values' => ['Contenance' => '30 ml'], 'brand_id' => $bella->id, 'price' => 8500, 'stock' => 7,
        ])->assertSessionHas('success');

        $variant = $this->ml25->fresh();
        $this->assertSame(['Contenance' => '30 ml'], $variant->option_values);
        $this->assertSame("contenance=30 ml|brand={$bella->id}", $variant->signature);
        $this->assertSame('GEL-DESHYDRATANT-25ML', $variant->sku);
        $this->assertSame('Gel déshydratant — 30 ml — Bella', $variant->label);
        $this->assertSame(7, $variant->stock);
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $variant->id, 'quantity' => -3, 'reason' => 'Correction manuelle (fiche produit)']);
    }

    /** @test */
    public function editing_cannot_create_a_duplicate()
    {
        $ml30 = ProductColor::create(['product_id' => $this->gel->id, 'color_id' => $this->ml25->color_id, 'option_values' => ['Contenance' => '30 ml'], 'price' => 1, 'stock' => 0]);

        $this->put(route('admin.products.variants.update', $ml30->id), ['values' => ['Contenance' => '25 ml'], 'price' => 1, 'stock' => 0])
            ->assertSessionHas('error');

        $this->assertSame(['Contenance' => '30 ml'], $ml30->fresh()->option_values);
    }

    /** @test */
    public function options_and_specs_can_be_edited_but_used_values_are_kept()
    {
        $this->put(route('admin.products.options.update', $this->gel->id), [
            'options' => [['name' => 'Contenance', 'values' => '25 ml, 50 ml'], ['name' => 'Parfum', 'values' => 'Neutre']],
            'specs' => [['key' => 'Origine', 'value' => 'Chine']],
        ])->assertSessionHas('success');

        $this->gel->refresh();
        $this->assertSame([['name' => 'Contenance', 'values' => ['25 ml', '50 ml']], ['name' => 'Parfum', 'values' => ['Neutre']]], $this->gel->options);
        $this->assertSame(['Origine' => 'Chine'], $this->gel->specs);

        // « 25 ml » est utilisé par une variante : il ne peut pas disparaître
        $this->put(route('admin.products.options.update', $this->gel->id), ['options' => [['name' => 'Contenance', 'values' => '30 ml']]])
            ->assertSessionHas('error');
        $this->assertSame(['25 ml', '50 ml'], $this->gel->fresh()->options[0]['values']);
    }

    /** @test */
    public function a_sold_variant_cannot_be_deleted_but_an_unsold_one_can()
    {
        app(SaleService::class)->createSale([['product_color_id' => $this->ml25->id, 'quantity' => 1]]);
        $unsold = ProductColor::create(['product_id' => $this->gel->id, 'color_id' => $this->ml25->color_id, 'option_values' => ['Contenance' => '30 ml'], 'price' => 1, 'stock' => 0]);

        $this->delete(route('admin.products.variants.destroy', $this->ml25->id))
            ->assertSessionHas('error', 'Erreur lors de la suppression de la variante : Cette variante a déjà été vendue : elle ne peut pas être supprimée.');
        $this->assertModelExists($this->ml25);

        $this->delete(route('admin.products.variants.destroy', $unsold->id))->assertSessionHas('success');
        $this->assertModelMissing($unsold);
    }

    /** @test */
    public function the_product_page_shows_options_brands_and_option_inputs()
    {
        Brand::factory()->create(['name' => 'Bella']);
        $this->gel->update(['specs' => ['Origine' => 'Chine']]);

        $this->get(route('admin.products.show', $this->gel->id))
            ->assertOk()
            ->assertSee('Contenance : 25 ml, 30 ml')
            ->assertSee('Origine')
            ->assertSee('name="values[Contenance]"', false)
            ->assertSee('Bella')
            ->assertSee('GEL-DESHYDRATANT-25ML')
            ->assertDontSee('name="color_id"', false);
    }
}
