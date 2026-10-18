<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marge par défaut (Paramètres › Stock & products) : à la création d'un produit, le prix de vente
 * est proposé à partir du prix d'achat (coût × (1 + marge)), et un prix saisi reste prioritaire.
 */
class DefaultMarginTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->category = Category::factory()->create(['name' => 'soins']);
    }

    private function margin(?float $percent): void
    {
        settings()->updateSettings(['default_margin_percent' => $percent]);
    }

    /** @test */
    public function the_margin_is_saved_in_the_settings_and_validated()
    {
        $this->put(route('admin.settings.update', 1), ['default_margin_percent' => 30])->assertRedirect(route('admin.settings.index'));
        $this->assertSame(30.0, Pricing::defaultMargin());

        $this->put(route('admin.settings.update', 1), ['default_margin_percent' => -5])->assertSessionHasErrors('default_margin_percent');
        $this->put(route('admin.settings.update', 1), ['default_margin_percent' => 'abc'])->assertSessionHasErrors('default_margin_percent');

        $this->get(route('admin.settings.index'))->assertOk()->assertSee('Default margin on cost (%)');
    }

    /** @test */
    public function the_suggested_price_is_the_cost_plus_the_margin()
    {
        $this->assertNull(Pricing::suggestedPrice(10000), 'sans marge configurée');

        $this->margin(30);
        $this->assertSame(13000.0, Pricing::suggestedPrice(10000));
        $this->assertSame(3412.5, Pricing::suggestedPrice('2625'));
        $this->assertNull(Pricing::suggestedPrice(null));
        $this->assertNull(Pricing::suggestedPrice(0));

        $this->margin(0);
        $this->assertSame(10000.0, Pricing::suggestedPrice(10000));
    }

    /** @test */
    public function a_new_product_takes_the_suggested_price_unless_a_price_is_typed()
    {
        $this->margin(30);

        $this->post(route('admin.products.store'), [
            'name' => 'Gel déshydratant',
            'category_id' => $this->category->id,
            'price' => 7000,
            'options' => [['name' => 'Contenance', 'values' => '25 ml, 30 ml, 50 ml']],
            'variants' => [
                // Coût sans prix : prix proposé
                ['enabled' => 1, 'values' => ['Contenance' => '25 ml'], 'price_purchase' => 5000],
                // Prix saisi : gardé
                ['enabled' => 1, 'values' => ['Contenance' => '30 ml'], 'price' => 9900, 'price_purchase' => 6000],
                // Ni prix ni coût : prix par défaut du produit
                ['enabled' => 1, 'values' => ['Contenance' => '50 ml']],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $prices = Product::where('name', 'Gel déshydratant')->firstOrFail()
            ->productColors()->orderBy('id')->pluck('price')->map(fn ($price) => (float) $price)->all();

        $this->assertSame([6500.0, 9900.0, 7000.0], $prices);
    }

    /** @test */
    public function without_margin_the_default_price_applies_as_before()
    {
        $this->post(route('admin.products.store'), [
            'name' => 'Savon',
            'category_id' => $this->category->id,
            'price' => 2000,
            'variants' => [['enabled' => 1, 'values' => [], 'price_purchase' => 1500]],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertEquals(2000, Product::where('name', 'Savon')->firstOrFail()->productColors()->value('price'));
    }

    /** @test */
    public function a_variant_added_to_a_product_also_gets_the_suggested_price()
    {
        $this->margin(25);
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 1000,
            'options' => [['name' => 'Contenance', 'values' => ['25 ml']]],
        ]);

        $variant = app(ProductColorService::class)->createVariant($product, ['values' => ['Contenance' => '30 ml'], 'price_purchase' => 8000]);

        $this->assertEquals(10000, $variant->price);
    }

    /** @test */
    public function the_creation_forms_carry_the_margin_for_the_live_suggestion()
    {
        $this->get(route('admin.products.create'))->assertOk()->assertDontSee('data-margin=', false);

        $this->margin(30);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->get(route('admin.products.create'))->assertOk()
            ->assertSee('data-margin="30"', false)
            ->assertSee('Price = cost + 30 %', false);

        // Fenêtre « Nouvelle variante » : proposé ; « Modifier la variante » : jamais recalculé
        $page = $this->get(route('admin.products.show', $product->id))->assertOk();
        $page->assertSee('data-margin="30"', false)->assertSee('Filled in from the purchase price + 30 %', false);
        $this->assertSame(1, substr_count($page->getContent(), 'data-pricing-cost'));
    }
}
