<?php

namespace Tests\Feature\Stock;

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
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Toute variation de stock doit laisser un mouvement : c'est ce qui rend l'historique
 * et les graphiques d'évolution fiables. Le catalogue crée les produits mais ne touche jamais au stock :
 * il vient des achats reçus, diminue avec les ventes, et se corrige par un mouvement (inventaire, correction).
 */
class StockHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function variant(int $stock = 0): ProductColor
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id,
            'color_id' => Color::factory()->create()->id,
            'stock' => $stock,
            'price' => 1000,
        ]);
    }

    /** @test */
    public function adjust_to_records_the_gap_as_a_movement()
    {
        $variant = $this->variant(10);
        $service = app(StockService::class);

        $out = $service->adjustTo($variant->id, 7, 'Inventaire');
        $this->assertSame(['out', -3, 10, 7], [$out->type, $out->quantity, $out->stock_before, $out->stock_after]);

        $in = $service->adjustTo($variant->id, 12, 'Inventaire');
        $this->assertSame(['in', 5, 7, 12], [$in->type, $in->quantity, $in->stock_before, $in->stock_after]);

        $this->assertNull($service->adjustTo($variant->id, 12, 'Inventaire'), 'Aucun mouvement si le stock est déjà à la cible');
        $this->assertSame(12, $variant->fresh()->stock);
        $this->assertSame(2, StockMovement::count());
    }

    /** @test */
    public function a_new_product_starts_every_variant_at_zero_without_movement()
    {
        $category = Category::factory()->create();

        // Un stock envoyé avec le formulaire (ancienne version de l'écran) est ignoré
        $this->post(route('admin.products.store'), [
            'name' => 'Wax',
            'category_id' => $category->id,
            'options' => [['name' => 'Couleur', 'values' => 'Rouge, Bleu']],
            'variants' => [
                ['enabled' => 1, 'values' => ['Couleur' => 'Rouge'], 'stock' => 5, 'price' => 1000],
                ['enabled' => 1, 'values' => ['Couleur' => 'Bleu'], 'price' => 1200],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame([0, 0], ProductColor::orderBy('id')->pluck('stock')->all());
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function a_new_variant_starts_at_zero()
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

        // Produit sans option : sa variante par défaut
        $this->post(route('admin.products.variants.store', $product->id), [
            'stock' => 8, 'price_purchase' => 500, 'price' => 1000,
        ])->assertSessionHas('success');

        $this->assertSame(0, ProductColor::where('product_id', $product->id)->sole()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function the_product_page_cannot_change_the_stock()
    {
        $variant = $this->variant(10);
        $payload = ['values' => $variant->option_values, 'price_purchase' => 500, 'price' => 1500];

        $this->put(route('admin.products.variants.update', $variant->id), $payload + ['stock' => 6])
            ->assertSessionHas('success');

        $this->assertEquals(1500, $variant->fresh()->price);
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function importing_products_never_changes_the_stock()
    {
        $import = fn (string $csv) => app(ImportService::class)->import(
            UploadedFile::fake()->createWithContent('products.csv', $csv),
            new ProductsImport(app(ProductService::class), app(SettingService::class), app(CategoryService::class), app(ProductColorService::class))
        );

        $import("name,price,category,color,stock\nWax,1000,tissu,rouge,5\n");
        $import("name,price,category,color,stock\nWax,1100,tissu,rouge,3\n");

        $variant = ProductColor::sole();
        $this->assertEquals(1100, $variant->price);
        $this->assertSame(0, $variant->stock);
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function the_stock_cannot_be_written_through_the_model()
    {
        $variant = $this->variant(10);

        // Non assignable en masse : ignoré
        $variant->update(['stock' => 3, 'price' => 2000]);
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertEquals(2000, $variant->fresh()->price);

        // Modification explicite : refusée
        $this->expectException(\LogicException::class);
        $variant->fresh()->forceFill(['stock' => 3])->save();
    }

    /** @test */
    public function the_stock_cannot_be_incremented_through_the_model()
    {
        $variant = $this->variant(10);

        try {
            $variant->increment('stock', 5);
            $this->fail('Le modèle ne doit pas modifier le stock');
        } catch (\LogicException) {
            $this->assertSame(10, $variant->fresh()->stock);
        }
    }

    /** @test */
    public function a_correction_is_a_movement_with_a_mandatory_reason()
    {
        $variant = $this->variant(10);

        $this->post(route('admin.movements.store'), ['product_color_id' => $variant->id, 'type' => 'out', 'quantity' => 2])
            ->assertSessionHasErrors('reason');
        $this->assertSame(10, $variant->fresh()->stock);

        $this->post(route('admin.movements.store'), [
            'product_color_id' => $variant->id, 'type' => 'out', 'quantity' => 2, 'reason' => 'Cassés pendant la livraison',
        ])->assertRedirect(route('admin.movements.index'));

        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $variant->id, 'type' => 'out', 'quantity' => -2,
            'stock_before' => 10, 'stock_after' => 8, 'reason' => 'Correction : Cassés pendant la livraison',
        ]);
    }

    /** @test */
    public function a_correction_cannot_remove_more_than_the_stock()
    {
        $variant = $this->variant(1);

        $this->post(route('admin.movements.store'), [
            'product_color_id' => $variant->id, 'type' => 'out', 'quantity' => 2, 'reason' => 'Erreur de comptage',
        ])->assertSessionHas('error');

        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }
}
