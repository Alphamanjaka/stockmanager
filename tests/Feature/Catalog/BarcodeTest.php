<?php

namespace Tests\Feature\Catalog;

use App\Core\Services\ImportService;
use App\Core\Services\SettingService;
use App\Models\User;
use App\Modules\Catalog\Imports\ProductsImport;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Catalog\Support\Barcode;
use App\Modules\Sale\Support\PosCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Code-barres des variantes : code du fabricant saisi ou importé, sinon code EAN-13 interne ;
 * la caisse le reconnaît au scan.
 */
class BarcodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create();
        $this->category = Category::factory()->create();
    }

    private function variant(array $attributes = []): ProductColor
    {
        // Produit sans option : une seule variante, modifiable sans combinaison
        $product = Product::factory()->create(['name' => 'Gel', 'category_id' => $this->category->id, 'options' => []]);

        return ProductColor::forceCreate($attributes + [
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'option_values' => [], 'stock' => 0, 'price' => 1000,
        ]);
    }

    private function updateVariant(ProductColor $variant, ?string $barcode)
    {
        return $this->actingAs($this->admin)->from(route('admin.products.show', $variant->product_id))
            ->put(route('admin.products.variants.update', $variant->id), [
                'values' => [], 'price' => 1000, 'price_purchase' => 500, 'barcode' => $barcode,
            ]);
    }

    private function import(string $csv): void
    {
        $this->actingAs($this->admin);
        $import = new ProductsImport(app(ProductService::class), app(SettingService::class), app(CategoryService::class), app(ProductColorService::class));
        app(ImportService::class)->import(UploadedFile::fake()->createWithContent('products.csv', $csv), $import);
    }

    /** @test */
    public function the_check_digit_follows_the_gtin_rule()
    {
        $this->assertTrue(Barcode::hasValidCheckDigit('4006381333931')); // EAN-13
        $this->assertTrue(Barcode::hasValidCheckDigit('036000291452'));  // UPC-A
        $this->assertTrue(Barcode::hasValidCheckDigit('96385074'));      // EAN-8
        $this->assertFalse(Barcode::hasValidCheckDigit('4006381333932'));
        $this->assertTrue(Barcode::hasValidCheckDigit('ABC-123'), 'Un code non numérique n\'a pas de clé');

        $this->assertSame('2000000000428', Barcode::internal(42));
        $this->assertSame('EAN-13', Barcode::symbology('2000000000428'));
        $this->assertSame('Code 128', Barcode::symbology('ABC-123'));
    }

    /** @test */
    public function a_new_variant_gets_an_internal_ean13()
    {
        $variant = $this->variant();

        $this->assertSame(Barcode::internal($variant->id), $variant->fresh()->barcode);
        $this->assertTrue(Barcode::hasValidCheckDigit($variant->fresh()->barcode));
    }

    /** @test */
    public function the_manufacturer_barcode_replaces_the_internal_one_and_clearing_it_restores_it()
    {
        $variant = $this->variant();

        $this->updateVariant($variant, '4006381333931')->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('4006381333931', $variant->fresh()->barcode);

        $this->updateVariant($variant, '')->assertSessionHasNoErrors();
        $this->assertSame(Barcode::internal($variant->id), $variant->fresh()->barcode);
    }

    /** @test */
    public function a_mistyped_or_duplicate_barcode_is_refused()
    {
        $taken = $this->variant(['barcode' => '4006381333931']);
        $variant = $this->variant();

        $this->updateVariant($variant, '4006381333932')->assertSessionHasErrors('barcode');
        $this->updateVariant($variant, $taken->barcode)->assertSessionHasErrors('barcode');
        $this->assertSame(Barcode::internal($variant->id), $variant->fresh()->barcode);
    }

    /** @test */
    public function an_internal_code_already_entered_as_a_manufacturer_code_is_not_reused()
    {
        // La variante suivante trouve son code interne en 20… déjà porté par une autre variante
        $first = $this->variant();
        $next = $first->id + 1;
        $first->update(['barcode' => Barcode::internal($next)]);

        $variant = $this->variant();

        $this->assertSame($next, $variant->id);
        $this->assertSame(Barcode::internal($next, 21), $variant->fresh()->barcode);
    }

    /** @test */
    public function the_checkout_and_the_variant_search_know_the_barcode()
    {
        $variant = $this->variant(['barcode' => '4006381333931']);

        $item = collect(app(PosCatalog::class)->build()['items'])->firstWhere('id', $variant->id);
        $this->assertSame('4006381333931', $item['barcode']);
        $this->assertSame([$variant->id], ProductColor::search('4006381333931')->pluck('id')->all());
    }

    /** @test */
    public function the_product_import_reads_the_barcode_written_as_a_number_by_excel()
    {
        $this->import("name,price,barcode\nGel,8000,4.006381333931E+12\n");

        $this->assertSame('4006381333931', ProductColor::sole()->barcode);

        // Réimport par le code-barres seul : même variante, prix mis à jour
        $this->import("name,price,barcode\nAutre nom,9000,4006381333931\n");

        $this->assertEquals(9000, ProductColor::sole()->price);
    }
}
