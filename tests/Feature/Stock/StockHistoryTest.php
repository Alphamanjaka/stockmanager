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
 * et les graphiques d'évolution fiables.
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

        return ProductColor::create([
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
    public function creating_a_product_records_the_initial_stock()
    {
        $category = Category::factory()->create();

        $this->post(route('admin.products.store'), [
            'name' => 'Wax',
            'category_id' => $category->id,
            'options' => [['name' => 'Couleur', 'values' => 'Rouge, Bleu']],
            'variants' => [
                ['enabled' => 1, 'values' => ['Couleur' => 'Rouge'], 'stock' => 5, 'price' => 1000],
                ['enabled' => 1, 'values' => ['Couleur' => 'Bleu'], 'stock' => 0, 'price' => 1200],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $rouge = ProductColor::where('option_values->Couleur', 'Rouge')->firstOrFail();
        $this->assertSame(5, $rouge->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $rouge->id, 'type' => 'in', 'quantity' => 5, 'stock_before' => 0, 'stock_after' => 5,
        ]);
        // Stock initial à 0 : rien à historiser
        $this->assertSame(1, StockMovement::count());
    }

    /** @test */
    public function adding_a_variant_records_its_initial_stock()
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

        // Produit sans option : sa variante par défaut
        $this->post(route('admin.products.variants.store', $product->id), [
            'stock' => 8, 'price_purchase' => 500, 'price' => 1000,
        ])->assertSessionHas('success');

        $variant = ProductColor::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(8, $variant->stock);
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $variant->id, 'type' => 'in', 'quantity' => 8]);
    }

    /** @test */
    public function editing_the_stock_on_the_product_page_records_a_correction()
    {
        $variant = $this->variant(10);
        $payload = ['values' => $variant->option_values, 'price_purchase' => 500, 'price' => 1000];

        $this->put(route('admin.products.variants.update', $variant->id), $payload + ['stock' => 6])
            ->assertSessionHas('success');

        $this->assertSame(6, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $variant->id, 'type' => 'out', 'quantity' => -4, 'reason' => 'Correction manuelle (fiche produit)',
        ]);

        // Modifier seulement le prix ne crée pas de mouvement
        $this->put(route('admin.products.variants.update', $variant->id), ['price' => 1500] + $payload + ['stock' => 6]);
        $this->assertSame(1, StockMovement::count());
    }

    /** @test */
    public function importing_products_records_stock_changes_and_ignores_empty_stock_cells()
    {
        $import = fn (string $csv) => app(ImportService::class)->import(
            UploadedFile::fake()->createWithContent('products.csv', $csv),
            new ProductsImport(app(ProductService::class), app(SettingService::class), app(CategoryService::class), app(ProductColorService::class))
        );

        $import("name,price,category,color,stock\nWax,1000,tissu,rouge,5\n");
        $variant = ProductColor::firstOrFail();
        $this->assertSame(5, $variant->stock);

        $import("name,price,category,color,stock\nWax,1000,tissu,rouge,3\n");
        $this->assertSame(3, $variant->fresh()->stock);

        // Cellule de stock vide : le stock existant est conservé
        $import("name,price,category,color,stock\nWax,1100,tissu,rouge,\n");
        $this->assertSame(3, $variant->fresh()->stock);

        $this->assertSame([5, -2], StockMovement::orderBy('id')->pluck('quantity')->all());
    }
}
