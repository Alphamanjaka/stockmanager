<?php

namespace Tests\Feature\Catalog;

use App\Core\Services\ImportService;
use App\Core\Services\SettingService;
use App\Models\User;
use App\Modules\Catalog\Imports\ProductsImport;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function import(string $csv): array
    {
        $import = new ProductsImport(app(ProductService::class), app(SettingService::class), app(CategoryService::class), app(ProductColorService::class));
        app(ImportService::class)->import(UploadedFile::fake()->createWithContent('products.csv', $csv), $import);

        return $import->getReport();
    }

    /** @test */
    public function one_row_per_variant_with_free_options_and_brands()
    {
        $report = $this->import(implode("\n", [
            'name,category,brand,option_contenance,option_couleur,option_taille,price,price_purchase,stock,alert_stock',
            'Gel déshydratant,soins,,25 ml,,,8000,5000,40,10',
            'Gel déshydratant,soins,,30 ml,,,9500,6000,12,',
            'Postiche lisse,postiches,Bella,,Noir,30 cm,45000,30000,4,2',
            'Postiche lisse,postiches,Bella,,Noir,40 cm,52000,35000,2,2',
        ]) . "\n");

        $this->assertSame(['created' => 4, 'updated' => 0], array_intersect_key($report, ['created' => 0, 'updated' => 0]));

        $gel = Product::where('name', 'Gel déshydratant')->firstOrFail();
        $this->assertSame([['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]], $gel->options);
        $this->assertSame(['GEL-DESHYDRATANT-25ML', 'GEL-DESHYDRATANT-30ML'], $gel->productColors()->orderBy('id')->pluck('sku')->all());
        $this->assertSame(40, ProductColor::where('sku', 'GEL-DESHYDRATANT-25ML')->value('stock'));

        $postiche = ProductColor::where('sku', 'POSTICHE-LISSE-NOIR-40CM-BELLA')->firstOrFail();
        $this->assertSame('Postiche lisse — Noir / 40 cm — Bella', $postiche->label);
        $this->assertSame('Bella', Brand::sole()->name);
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $postiche->id, 'type' => 'in', 'quantity' => 2, 'reason' => 'Stock initial (nouvelle variante)']);
    }

    /** @test */
    public function reimporting_updates_instead_of_duplicating()
    {
        $csv = "name,category,option_contenance,price,stock\nGel,soins,25 ml,8000,40\n";
        $this->import($csv);

        $report = $this->import("name,category,option_contenance,price,stock\ngel,soins,25 ml,8500,35\n");

        $this->assertSame(1, $report['updated']);
        $this->assertSame(1, ProductColor::count(), 'Même produit (casse ignorée) et même combinaison : pas de doublon');
        $variant = ProductColor::sole();
        $this->assertEquals(8500, $variant->price);
        $this->assertSame(35, $variant->stock);
        $this->assertDatabaseHas('stock_movements', ['product_color_id' => $variant->id, 'quantity' => -5, 'reason' => 'Import Excel']);
    }

    /** @test */
    public function a_sku_column_targets_an_existing_variant()
    {
        $this->import("name,option_contenance,price,stock\nGel,25 ml,8000,40\n");

        $this->import("name,sku,price,stock\nGel,GEL-25ML,9000,\n");

        $variant = ProductColor::sole();
        $this->assertEquals(9000, $variant->price);
        $this->assertSame(40, $variant->stock, 'Cellule de stock vide : stock inchangé');
    }

    /** @test */
    public function the_legacy_color_column_still_works()
    {
        $this->import("name,price,category,color,stock\nWax,1000,tissu,Rouge,5\nPeigne,500,soins,No Variant,1\n");

        $product = Product::where('name', 'Wax')->firstOrFail();
        $this->assertSame([['name' => 'Couleur', 'values' => ['Rouge']]], $product->options);
        $this->assertSame(['Couleur' => 'Rouge'], ProductColor::where('sku', 'WAX-ROUGE')->firstOrFail()->option_values);
        $this->assertSame(Color::where('name', 'Rouge')->value('id'), ProductColor::where('sku', 'WAX-ROUGE')->value('color_id'));

        // « No Variant » : produit sans option, une variante par défaut
        $this->assertSame([], ProductColor::where('sku', 'PEIGNE')->firstOrFail()->option_values);
    }

    /** @test */
    public function an_incomplete_row_cancels_the_whole_import_with_its_line_number()
    {
        Category::factory()->create(['name' => 'postiches']);

        try {
            $this->import(implode("\n", [
                'name,category,option_couleur,option_taille,price',
                'Postiche,postiches,Noir,30 cm,45000',
                'Postiche,postiches,Brun,,45000', // taille manquante
            ]) . "\n");
            $this->fail('L\'import aurait dû échouer');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Erreur ligne 3', $e->getMessage());
            $this->assertStringContainsString('Taille', $e->getMessage());
        }

        $this->assertSame(0, Product::count(), 'Tout l\'import est annulé');
        $this->assertSame(0, ProductColor::count());
    }

    /** @test */
    public function the_csv_template_lists_the_option_columns()
    {
        $content = $this->get(route('admin.imports.template', 'products'))->assertOk()->streamedContent();

        $this->assertStringContainsString('option_couleur', $content);
        $this->assertStringContainsString('brand', $content);
        $this->assertStringContainsString('sku', $content);
    }
}
