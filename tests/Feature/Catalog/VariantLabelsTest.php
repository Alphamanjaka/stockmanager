<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deux variantes d'un même produit (gel 25 ml / 30 ml) doivent se distinguer partout.
 */
class VariantLabelsTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $ml25;
    private ProductColor $ml30;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());

        $gel = Product::factory()->create([
            'name' => 'Gel déshydratant',
            'category_id' => Category::factory()->create()->id,
            'options' => [['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]],
        ]);
        $noVariant = Color::factory()->create(['name' => 'Sans variante']);

        foreach (['25 ml' => 'ml25', '30 ml' => 'ml30'] as $value => $property) {
            $this->{$property} = ProductColor::forceCreate([
                'product_id' => $gel->id, 'color_id' => $noVariant->id,
                'option_values' => ['Contenance' => $value], 'stock' => 5, 'price' => 8000,
            ]);
        }
    }

    /** @test */
    public function sale_pages_show_which_variant_was_sold()
    {
        $sale = app(SaleService::class)->createSale([
            ['product_color_id' => $this->ml25->id, 'quantity' => 1],
            ['product_color_id' => $this->ml30->id, 'quantity' => 2],
        ]);

        $this->get(route('admin.sales.show', $sale->id))
            ->assertOk()
            ->assertSee('Gel déshydratant — 25 ml')
            ->assertSee('Gel déshydratant — 30 ml');
        $this->get(route('admin.sales.pdf', $sale->id))->assertOk();
    }

    /** @test */
    public function the_insufficient_stock_message_names_the_variant()
    {
        $this->expectExceptionMessage('Stock insuffisant pour : Gel déshydratant — 30 ml');

        app(SaleService::class)->createSale([['product_color_id' => $this->ml30->id, 'quantity' => 99]]);
    }

    /** @test */
    public function stock_history_and_inventory_show_the_variant()
    {
        app(StockService::class)->removeStock($this->ml30->id, 1, 'Vente');

        $this->getJson("/api/products/{$this->ml30->product_id}/movements")
            ->assertOk()
            ->assertJsonPath('data.0.variant_label', 'Gel déshydratant — 30 ml');

        $inventory = app(InventoryService::class)->create(null, null, auth()->id());
        $this->get(route('admin.inventories.show', $inventory->id))
            ->assertOk()
            ->assertSee('25 ml')
            ->assertSee('GEL-DESHYDRATANT-30ML');
    }

    /** @test */
    public function the_purchase_product_search_uses_labels_and_skus()
    {
        // Le bon de commande cherche dans le libellé et le SKU de chaque variante (resources/js/purchase-form.js)
        $this->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertViewHas('formData', fn ($data) => $data['catalog']->pluck('name')->contains('Gel déshydratant — 30 ml')
                && $data['catalog']->firstWhere('id', $this->ml30->id)['sku'] === 'GEL-DESHYDRATANT-30ML');
    }
}
