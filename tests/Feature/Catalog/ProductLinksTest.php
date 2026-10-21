<?php

namespace Tests\Feature\Catalog;

use App\Core\Services\GlobalSearchService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les listes affichent des variantes, mais la fiche est celle du produit :
 * les liens doivent utiliser product_id, pas l'id de la variante.
 */
class ProductLinksTest extends TestCase
{
    use RefreshDatabase;

    private Product $second;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create(['name' => 'tissu']);
        $first = Product::factory()->create(['name' => 'Wax', 'category_id' => $category->id]);
        $this->second = Product::factory()->create(['name' => 'Velours', 'category_id' => $category->id]);

        // Ids de variantes : 1 (Wax), 2 et 3 (Velours) ; ids produits : 1 et 2
        foreach ([[$first, 'Rouge'], [$this->second, 'Bleu'], [$this->second, 'Vert']] as [$product, $color]) {
            ProductColor::forceCreate([
                'product_id' => $product->id,
                'color_id' => Color::factory()->create(['name' => $color])->id,
                'stock' => 10,
                'price' => 1000,
            ]);
        }
    }

    /** @test */
    public function global_search_links_variants_to_their_product_page()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $urls = app(GlobalSearchService::class)->search('Velours')
            ->where('type', 'product')
            ->pluck('url')
            ->unique()
            ->values()
            ->all();

        $this->assertSame([route('admin.products.show', $this->second->id)], $urls);
    }

    /** @test */
    public function product_list_links_variants_to_their_product_page()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('href="'.route('admin.products.show', $this->second->id).'"', false)
            ->assertDontSee('href="'.route('admin.products.show', 3).'"', false);
    }
}
