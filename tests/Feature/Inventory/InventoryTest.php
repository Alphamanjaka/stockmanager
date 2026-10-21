<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $wax;
    private ProductColor $velours;
    private Category $tissu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());

        $this->tissu = Category::factory()->create(['name' => 'tissu']);
        $other = Category::factory()->create(['name' => 'creme']);
        $this->wax = $this->variant('Wax', $this->tissu, 10);
        $this->velours = $this->variant('Velours', $this->tissu, 4);
        $this->variant('Karité', $other, 7);
    }

    private function variant(string $name, Category $category, int $stock): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => $category->id]);

        return ProductColor::forceCreate(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => $stock, 'price' => 1000]);
    }

    private function open(?Category $category = null): Inventory
    {
        $this->post(route('admin.inventories.store'), ['category_id' => $category?->id])->assertSessionHasNoErrors();

        return Inventory::latest('id')->firstOrFail();
    }

    private function lineId(Inventory $inventory, ProductColor $variant): int
    {
        return $inventory->lines()->where('product_color_id', $variant->id)->value('id');
    }

    /** @test */
    public function opening_a_count_snapshots_the_stock_of_the_scope()
    {
        $all = $this->open();
        $this->assertSame(3, $all->lines()->count());

        $scoped = $this->open($this->tissu);
        $this->assertEqualsCanonicalizing(
            [$this->wax->id => 10, $this->velours->id => 4],
            $scoped->lines()->pluck('expected_quantity', 'product_color_id')->all()
        );
        $this->assertSame(Inventory::DRAFT, $scoped->status);
        $this->assertStringStartsWith('INV-', $scoped->reference);
    }

    /** @test */
    public function counts_can_be_saved_without_touching_the_stock()
    {
        $inventory = $this->open($this->tissu);

        $this->put(route('admin.inventories.update', $inventory->id), [
            'counts' => [$this->lineId($inventory, $this->wax) => 8, $this->lineId($inventory, $this->velours) => ''],
        ])->assertRedirect(route('admin.inventories.show', $inventory->id));

        $this->assertSame(8, $inventory->lines()->find($this->lineId($inventory, $this->wax))->counted_quantity);
        $this->assertNull($inventory->lines()->find($this->lineId($inventory, $this->velours))->counted_quantity);
        $this->assertSame(10, $this->wax->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** @test */
    public function validating_sets_counted_stock_with_movements_and_ignores_uncounted_lines()
    {
        $inventory = $this->open($this->tissu);

        // Une vente pendant le comptage : le stock réel au moment de la validation fait foi
        app(StockService::class)->removeStock($this->wax->id, 1, 'Vente');

        $this->put(route('admin.inventories.validate', $inventory->id), [
            'counts' => [$this->lineId($inventory, $this->wax) => 7],
        ])->assertRedirect(route('admin.inventories.show', $inventory->id));

        $inventory->refresh();
        $this->assertSame(Inventory::VALIDATED, $inventory->status);
        $this->assertNotNull($inventory->validated_at);

        $this->assertSame(7, $this->wax->fresh()->stock);
        $this->assertSame(4, $this->velours->fresh()->stock, 'Une ligne non comptée ne change pas le stock');
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $this->wax->id, 'type' => 'out', 'quantity' => -2,
            'stock_before' => 9, 'stock_after' => 7, 'reason' => "Inventaire {$inventory->reference}",
        ]);

        $line = $inventory->lines()->find($this->lineId($inventory, $this->wax));
        $this->assertSame(9, $line->stock_at_validation);
        $this->assertSame(-2, $line->gap());
    }

    /** @test */
    public function sales_and_receipts_made_after_a_line_is_counted_are_kept()
    {
        $inventory = $this->open($this->tissu);
        $waxLine = $this->lineId($inventory, $this->wax);
        $veloursLine = $this->lineId($inventory, $this->velours);

        // Wax : 10 comptés pour 10 en stock. Velours : 3 comptés pour 4 en stock (1 manquant)
        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => 10, $veloursLine => 3]]);
        $this->assertSame(10, $inventory->lines()->find($waxLine)->stock_at_count);
        $this->assertSame(4, $inventory->lines()->find($veloursLine)->stock_at_count);

        // Puis, avant la validation : 3 wax vendus, 5 velours reçus
        app(StockService::class)->removeStock($this->wax->id, 3, 'Vente');
        app(StockService::class)->addStock($this->velours->id, 5, 'Réception Achat');
        $this->get(route('admin.inventories.show', $inventory->id))->assertOk()->assertSee('Stock when counted: 10.');

        // Le formulaire renvoie les mêmes comptages : ils gardent le stock relevé au comptage
        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 10, $veloursLine => 3]]);

        $this->assertSame(7, $this->wax->fresh()->stock, 'La vente faite après le comptage est conservée');
        $this->assertSame(8, $this->velours->fresh()->stock, 'Seul l\'écart du comptage est appliqué');
        $this->assertDatabaseMissing('stock_movements', ['product_color_id' => $this->wax->id, 'reason' => "Inventaire {$inventory->reference}"]);
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $this->velours->id, 'type' => 'out', 'quantity' => -1,
            'stock_before' => 9, 'stock_after' => 8, 'reason' => "Inventaire {$inventory->reference}",
        ]);

        $velours = $inventory->lines()->find($veloursLine);
        $this->assertSame(-1, $velours->gap());
        $this->assertSame(9, $velours->stock_at_validation);
        $this->assertSame(8, $velours->stockAfterValidation());
        $this->assertSame(0, $inventory->lines()->find($waxLine)->gap());
    }

    /** @test */
    public function recounting_a_line_measures_the_gap_against_the_stock_of_the_new_count()
    {
        $inventory = $this->open($this->tissu);
        $waxLine = $this->lineId($inventory, $this->wax);

        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => 8]]);
        app(StockService::class)->removeStock($this->wax->id, 2, 'Vente');

        // Recompté après la vente : nouvelle photo du stock (8)
        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => 6]]);
        $this->assertSame(8, $inventory->lines()->find($waxLine)->stock_at_count);

        // Effacé : plus de comptage ni de photo
        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => '']]);
        $this->assertNull($inventory->lines()->find($waxLine)->stock_at_count);

        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 6]]);
        $this->assertSame(6, $this->wax->fresh()->stock);
        $this->assertSame(-2, $inventory->lines()->find($waxLine)->gap());
    }

    /** @test */
    public function the_stock_never_goes_below_zero_at_validation()
    {
        $inventory = $this->open($this->tissu);
        $waxLine = $this->lineId($inventory, $this->wax);

        // 2 comptés pour 10, puis 5 vendus : 5 − 8 ramené à 0
        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => 2]]);
        app(StockService::class)->removeStock($this->wax->id, 5, 'Vente');
        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 2]]);

        $this->assertSame(0, $this->wax->fresh()->stock);
        $this->assertSame(0, $inventory->lines()->find($waxLine)->stockAfterValidation());
    }

    /** @test */
    public function a_line_counted_before_the_stock_at_count_was_recorded_is_set_to_the_counted_quantity()
    {
        $inventory = $this->open($this->tissu);
        $waxLine = $this->lineId($inventory, $this->wax);
        // Comptage enregistré avant la migration : pas de stock au comptage
        $inventory->lines()->whereKey($waxLine)->update(['counted_quantity' => 7]);
        app(StockService::class)->removeStock($this->wax->id, 1, 'Vente');

        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 7]]);

        $this->assertSame(7, $this->wax->fresh()->stock);
        $line = $inventory->lines()->find($waxLine);
        $this->assertSame(9, $line->stock_at_count);
        $this->assertSame(-2, $line->gap());
    }

    /** @test */
    public function a_validated_count_is_final()
    {
        $inventory = $this->open($this->tissu);
        $waxLine = $this->lineId($inventory, $this->wax);
        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 7]]);

        // Revalider, modifier ou supprimer : refusé, sans effet sur le stock
        $this->put(route('admin.inventories.validate', $inventory->id), ['counts' => [$waxLine => 1]])->assertSessionHas('error');
        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$waxLine => 1]])->assertSessionHas('error');
        $this->delete(route('admin.inventories.destroy', $inventory->id))->assertSessionHas('error');

        $this->assertSame(7, $this->wax->fresh()->stock);
        $this->assertSame(7, $inventory->lines()->find($waxLine)->counted_quantity);
        $this->assertModelExists($inventory);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    /** @test */
    public function a_draft_can_be_deleted()
    {
        $inventory = $this->open();

        $this->delete(route('admin.inventories.destroy', $inventory->id))->assertRedirect(route('admin.inventories.index'));

        $this->assertModelMissing($inventory);
        $this->assertDatabaseCount('inventory_lines', 0);
    }

    /** @test */
    public function negative_counts_are_rejected()
    {
        $inventory = $this->open($this->tissu);

        $this->put(route('admin.inventories.update', $inventory->id), ['counts' => [$this->lineId($inventory, $this->wax) => -3]])
            ->assertSessionHasErrors('counts.' . $this->lineId($inventory, $this->wax));
    }

    /** @test */
    public function inventory_pages_render_for_draft_and_validated_counts()
    {
        $draft = $this->open($this->tissu);
        $validated = $this->open();
        $this->put(route('admin.inventories.validate', $validated->id), ['counts' => [$this->lineId($validated, $this->wax) => 12]]);

        $this->get(route('admin.inventories.index'))->assertOk()->assertSee($draft->reference);
        $this->get(route('admin.inventories.create'))->assertOk();
        $this->get(route('admin.inventories.show', $draft->id))->assertOk()->assertSee('Validate and adjust stock');
        $this->get(route('admin.inventories.show', $validated->id))->assertOk()->assertSee('+2')->assertSee('10 → 12')->assertDontSee('Validate and adjust stock');
    }

    /** @test */
    public function sellers_cannot_access_stock_counts()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->get(route('admin.inventories.index'))->assertRedirect(route('dashboard'));
    }
}
