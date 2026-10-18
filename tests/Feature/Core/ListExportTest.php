<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Catalog\Exports\ProductsExport;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Exports\SuppliersExport;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Stock\Exports\StockMovementsExport;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exports des listes (App\Core\Exports\ListExport) : exactement ce que la page affiche
 * (mêmes filtres, même statut), ou la sélection des cases à cocher.
 */
class ListExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function variant(string $name, string $category = 'perruques', array $attributes = []): ProductColor
    {
        $product = Product::factory()->create([
            'name' => $name,
            'category_id' => Category::firstOrCreate(['name' => $category])->id,
        ]);

        return ProductColor::forceCreate($attributes + [
            'product_id' => $product->id,
            'color_id' => Color::factory()->create()->id,
            'stock' => 4,
            'price' => 2500,
        ]);
    }

    /** @test */
    public function the_pdf_and_excel_files_are_downloaded_with_a_dated_name()
    {
        $this->variant('perruque lisse');

        $this->get(route('admin.exports.download', ['list' => 'products', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('products-' . now()->format('Y-m-d') . '.pdf');

        $this->get(route('admin.exports.download', ['list' => 'products', 'format' => 'xlsx']))
            ->assertOk()
            ->assertDownload('products-' . now()->format('Y-m-d') . '.xlsx');
    }

    /** @test */
    public function unknown_lists_or_formats_are_not_found()
    {
        $this->get('/admin/exports/nothing/pdf')->assertNotFound();
        $this->get('/admin/exports/products/docx')->assertNotFound();
    }

    /** @test */
    public function only_the_back_office_can_export()
    {
        $this->actingAs(User::factory()->frontOffice()->create());
        $this->get(route('admin.exports.download', ['list' => 'suppliers', 'format' => 'pdf']))->assertRedirect();

        auth()->logout();
        $this->get(route('admin.exports.download', ['list' => 'suppliers', 'format' => 'pdf']))->assertRedirect(route('login'));
    }

    /** @test */
    public function the_export_follows_the_list_filters()
    {
        $this->variant('perruque lisse', 'perruques');
        $this->variant('gel coiffant', 'soins');
        $this->variant('perruque frisée', 'perruques')->product->archive();

        $export = app(ProductsExport::class);
        $names = fn (array $filters) => $export->rows($filters)->map(fn (ProductColor $variant) => $variant->product->name)->all();

        // Tri par défaut : nom du produit
        $this->assertSame(['gel coiffant', 'perruque lisse'], $names([]));
        $this->assertSame(['perruque lisse'], $names(['search' => 'perruque']));
        $this->assertSame(['perruque frisée'], $names(['status' => 'archived']));
        $this->assertSame(['gel coiffant'], $names(['category' => (string) Category::where('name', 'soins')->value('id')]));
    }

    /** @test */
    public function the_selection_restricts_the_export()
    {
        $kept = Supplier::factory()->create(['name' => 'Tissus Mada']);
        Supplier::factory()->create(['name' => 'Autre']);

        $rows = app(SuppliersExport::class)->rows([], [$kept->id]);

        $this->assertSame(['Tissus Mada'], $rows->pluck('name')->all());
    }

    /** @test */
    public function selected_product_rows_export_every_variant_of_the_product()
    {
        $variant = $this->variant('perruque lisse');
        ProductColor::forceCreate(['product_id' => $variant->product_id, 'color_id' => Color::factory()->create()->id, 'price' => 1, 'stock' => 0]);
        $this->variant('gel coiffant');

        $rows = app(ProductsExport::class)->rows([], [$variant->product_id]);

        $this->assertCount(2, $rows);
        $this->assertSame([$variant->product_id], $rows->pluck('product_id')->unique()->values()->all());
    }

    /** @test */
    public function the_document_describes_the_applied_filters()
    {
        $filters = app(StockMovementsExport::class)->describeFilters(
            ['search' => 'vente', 'type' => 'out', 'date_from' => '2026-10-01', 'status' => 'archived'],
            [1, 2]
        );

        $this->assertSame([
            'Selection' => '2 item(s)',
            'Search' => '« vente »',
            'Type' => 'Stock out',
            'From' => '01/10/2026',
            'Status' => 'Archived',
        ], $filters);
    }

    /** @test */
    public function the_date_range_of_the_movements_includes_its_last_day()
    {
        $movement = StockMovement::factory()->create(['product_color_id' => $this->variant('perruque lisse')->id]);
        $movement->forceFill(['created_at' => now()->setTime(18, 30)])->save();

        $rows = app(StockMovementsExport::class)->rows(['date_to' => now()->toDateString()]);

        $this->assertSame([$movement->id], $rows->pluck('id')->all());
    }
}
