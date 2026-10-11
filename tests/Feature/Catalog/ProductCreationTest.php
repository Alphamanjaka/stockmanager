<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Création d'un produit avec ses options et sa grille de variantes.
 */
class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->category = Category::factory()->create(['name' => 'soins']);
    }

    private function store(array $data)
    {
        return $this->post(route('admin.products.store'), $data + ['category_id' => $this->category->id]);
    }

    /** @test */
    public function a_desiccant_gel_is_created_with_one_variant_per_volume()
    {
        $this->store([
            'name' => 'Gel déshydratant',
            'options' => [['name' => 'Contenance', 'values' => '25 ml, 30 ml']],
            'variants' => [
                ['enabled' => 1, 'values' => ['Contenance' => '25 ml'], 'price' => 8000, 'price_purchase' => 5000, 'stock' => 40],
                ['enabled' => 1, 'values' => ['Contenance' => '30 ml'], 'price' => 9500, 'stock' => 0],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Gel déshydratant')->firstOrFail();
        $this->assertSame([['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]], $product->options);

        $variants = $product->productColors()->orderBy('id')->get();
        $this->assertSame(['GEL-DESHYDRATANT-25ML', 'GEL-DESHYDRATANT-30ML'], $variants->pluck('sku')->all());
        $this->assertSame(['Contenance' => '25 ml'], $variants[0]->option_values);
        $this->assertSame(40, $variants[0]->stock);
        $this->assertEquals(9500, $variants[1]->price);

        // Stock initial historisé, couleur technique conservée pour l'ancienne version du code
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $variants[0]->id, 'type' => 'in', 'quantity' => 40]);
        $this->assertSame('Sans variante', $variants[0]->color->name);
    }

    /** @test */
    public function a_wig_gets_the_checked_combinations_with_their_brands()
    {
        $bella = Brand::factory()->create(['name' => 'Bella']);
        $noir = Color::factory()->create(['name' => 'Noir']);

        $this->store([
            'name' => 'Postiche lisse',
            'options' => [['name' => 'Couleur', 'values' => 'Noir, Brun'], ['name' => 'Taille', 'values' => '30 cm, 40 cm']],
            'variants' => [
                ['enabled' => 1, 'values' => ['Couleur' => 'Noir', 'Taille' => '30 cm'], 'brand_id' => $bella->id, 'price' => 45000],
                ['enabled' => 1, 'values' => ['Couleur' => 'Noir', 'Taille' => '40 cm'], 'price' => 52000],
                ['enabled' => 1, 'values' => ['Couleur' => 'Brun', 'Taille' => '30 cm'], 'price' => 45000],
                ['values' => ['Couleur' => 'Brun', 'Taille' => '40 cm'], 'price' => 52000], // décochée : non créée
            ],
        ])->assertRedirect(route('admin.products.index'));

        $variants = Product::where('name', 'Postiche lisse')->firstOrFail()->productColors()->orderBy('id')->get();
        $this->assertCount(3, $variants);
        $this->assertSame('POSTICHE-LISSE-NOIR-30CM-BELLA', $variants[0]->sku);
        $this->assertSame('Postiche lisse — Noir / 30 cm — Bella', $variants[0]->label);
        $this->assertSame('Postiche lisse — Brun / 30 cm', $variants[2]->label);

        // Double écriture : la couleur existante est réutilisée, la nouvelle est créée
        $this->assertSame($noir->id, $variants[0]->color_id);
        $this->assertSame('Brun', $variants[2]->color->name);
    }

    /** @test */
    public function a_product_without_options_gets_a_single_default_variant()
    {
        $this->store([
            'name' => 'Peigne',
            'price' => 2000,
            'variants' => [['enabled' => 1, 'values' => [], 'stock' => 10]],
        ])->assertRedirect(route('admin.products.index'));

        $variant = Product::where('name', 'Peigne')->firstOrFail()->productColors()->sole();
        $this->assertSame([], $variant->option_values);
        $this->assertSame('PEIGNE', $variant->sku);
        $this->assertEquals(2000, $variant->price, 'Le prix par défaut du produit s\'applique');
    }

    /** @test */
    public function specifications_are_stored_as_key_values()
    {
        $this->store([
            'name' => 'Gel',
            'specs' => [['key' => 'Origine', 'value' => 'Chine'], ['key' => '', 'value' => 'ignorée']],
            'variants' => [['enabled' => 1, 'values' => []]],
        ]);

        $this->assertSame(['Origine' => 'Chine'], Product::where('name', 'Gel')->firstOrFail()->specs);
    }

    /** @test */
    public function the_grid_must_be_consistent()
    {
        $options = [['name' => 'Contenance', 'values' => '25 ml, 30 ml']];

        // Aucune variante retenue
        $this->store(['name' => 'A', 'options' => $options, 'variants' => [['values' => ['Contenance' => '25 ml']]]])
            ->assertSessionHasErrors('variants');
        // Valeur absente des options
        $this->store(['name' => 'B', 'options' => $options, 'variants' => [['enabled' => 1, 'values' => ['Contenance' => '50 ml']]]])
            ->assertSessionHasErrors('variants');
        // Même combinaison deux fois
        $this->store(['name' => 'C', 'options' => $options, 'variants' => [
            ['enabled' => 1, 'values' => ['Contenance' => '25 ml']],
            ['enabled' => 1, 'values' => ['Contenance' => '25 ml']],
        ]])->assertSessionHasErrors('variants');
        // Crochets interdits dans un nom d'option
        $this->store(['name' => 'D', 'options' => [['name' => 'Taille[x]', 'values' => 'S']], 'variants' => [['enabled' => 1, 'values' => []]]])
            ->assertSessionHasErrors('options.0.name');

        $this->assertSame(0, Product::count());
    }

    /** @test */
    public function the_creation_page_offers_category_templates_and_brands()
    {
        $this->category->update(['option_template' => [['name' => 'Contenance', 'values' => ['25 ml']]]]);
        Brand::factory()->create(['name' => 'Bella']);

        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('"Contenance"', false)
            ->assertSee('Bella');
    }
}
