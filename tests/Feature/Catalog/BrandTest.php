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

class BrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function brands_can_be_created_renamed_and_listed()
    {
        $this->post(route('admin.brands.store'), ['name' => 'Bella'])->assertRedirect(route('admin.brands.index'));
        $brand = Brand::where('name', 'Bella')->firstOrFail();

        $this->put(route('admin.brands.update', $brand->id), ['name' => 'Bella Hair'])->assertRedirect(route('admin.brands.index'));
        $this->assertSame('Bella Hair', $brand->fresh()->name);

        $this->get(route('admin.brands.index'))->assertOk()->assertSee('Bella Hair');
        $this->get(route('admin.brands.create'))->assertOk();
        $this->get(route('admin.brands.edit', $brand->id))->assertOk();
    }

    /** @test */
    public function brand_names_are_required_and_unique()
    {
        Brand::factory()->create(['name' => 'Bella']);

        $this->post(route('admin.brands.store'), ['name' => 'Bella'])->assertSessionHasErrors('name');
        $this->post(route('admin.brands.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->assertSame(1, Brand::count());
    }

    /** @test */
    public function a_brand_in_use_stays_archived_but_an_unused_archived_one_can_be_deleted()
    {
        $used = Brand::factory()->create(['name' => 'Bella']);
        $unused = Brand::factory()->create(['name' => 'Ancienne']);
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $variant = ProductColor::forceCreate(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'brand_id' => $used->id, 'price' => 1, 'stock' => 0]);
        $used->archive();
        $unused->archive();

        $this->delete(route('admin.brands.destroy', $used->id))->assertSessionHas('error');
        $this->assertModelExists($used);
        $this->assertSame($used->id, $variant->fresh()->brand_id);

        $this->delete(route('admin.brands.destroy', $unused->id))->assertRedirect(route('admin.brands.index', ['status' => 'archived']));
        $this->assertModelMissing($unused);
    }
}
