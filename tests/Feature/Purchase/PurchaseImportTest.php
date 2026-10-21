<?php

namespace Tests\Feature\Purchase;

use App\Core\Services\ImportService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Services\ProductColorService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Contact\Services\SupplierService;
use App\Modules\Purchase\Imports\PurchaseImport;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PurchaseImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function purchase_lines_target_variants_by_sku_or_by_legacy_name_and_color()
    {
        $this->actingAs(User::factory()->backOffice()->create());
        Supplier::factory()->create(['email' => 'contact@tissus.mg']);

        $category = Category::factory()->create();
        $noVariant = Color::factory()->create(['name' => 'Sans variante']);
        $gel = Product::factory()->create(['name' => 'Gel', 'category_id' => $category->id, 'options' => [['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]]]);
        foreach (['25 ml', '30 ml'] as $value) {
            ProductColor::forceCreate(['product_id' => $gel->id, 'color_id' => $noVariant->id, 'option_values' => ['Contenance' => $value], 'price' => 1, 'stock' => 0]);
        }
        $wax = Product::factory()->create(['name' => 'Wax', 'category_id' => $category->id]);
        $rouge = ProductColor::forceCreate(['product_id' => $wax->id, 'color_id' => Color::factory()->create(['name' => 'Rouge'])->id, 'price' => 1, 'stock' => 0]);

        $csv = implode("\n", [
            'reference_groupe,email_fournisseur,sku,nom_produit,couleur_produit,quantite,cout_unitaire',
            'A1,contact@tissus.mg,GEL-30ML,,,12,5000',
            'A1,contact@tissus.mg,,Wax,Rouge,3,700',
        ])."\n";

        $import = new PurchaseImport(app(PurchaseService::class), app(SupplierService::class), app(ProductService::class), app(ProductColorService::class));
        app(ImportService::class)->import(UploadedFile::fake()->createWithContent('purchases.csv', $csv), $import);

        $lines = PurchaseItem::orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame(ProductColor::where('sku', 'GEL-30ML')->value('id'), $lines[0]->product_color_id, 'Le SKU désigne la bonne contenance');
        $this->assertSame(12, $lines[0]->quantity);
        $this->assertSame($rouge->id, $lines[1]->product_color_id);
    }
}
