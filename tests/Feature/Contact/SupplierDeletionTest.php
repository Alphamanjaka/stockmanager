<?php

namespace Tests\Feature\Contact;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un fournisseur ne se supprime pas tant qu'il a des achats : il s'archive, et ses achats le gardent.
 */
class SupplierDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());

        $this->supplier = Supplier::factory()->create(['name' => 'Tissus Mada']);
        $this->purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'state' => 'Received']);

        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $variant = ProductColor::forceCreate(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 5, 'price' => 1000]);
        PurchaseItem::create(['purchase_id' => $this->purchase->id, 'product_color_id' => $variant->id, 'quantity' => 5, 'unit_price' => 700, 'subtotal' => 3500]);
    }

    /** @test */
    public function a_supplier_is_archived_and_its_purchases_keep_it()
    {
        $this->post(route('admin.suppliers.archive'), ['ids' => [$this->supplier->id]])->assertSessionHas('success');

        $this->assertTrue($this->supplier->fresh()->isArchived());
        $this->assertSame($this->supplier->id, $this->purchase->fresh()->supplier_id);
        $this->get(route('admin.purchases.show', $this->purchase->id))->assertOk()->assertSee('Tissus Mada');
        $this->get(route('admin.suppliers.show', $this->supplier->id))->assertOk()->assertSee('archived');
    }

    /** @test */
    public function a_supplier_with_purchases_cannot_be_deleted_even_archived()
    {
        $this->supplier->archive();

        $this->delete(route('admin.suppliers.destroy', $this->supplier->id))->assertSessionHas('error');

        $this->assertModelExists($this->supplier);
        $this->assertSame($this->supplier->id, $this->purchase->fresh()->supplier_id);
        $this->assertSame(1, $this->purchase->items()->count());
    }

    /** @test */
    public function an_archived_supplier_without_purchase_can_be_deleted()
    {
        $unused = Supplier::factory()->create();
        $unused->archive();

        $this->delete(route('admin.suppliers.destroy', $unused->id))
            ->assertRedirect(route('admin.suppliers.index', ['status' => 'archived']));

        $this->assertModelMissing($unused);
    }

    /** @test */
    public function the_database_itself_refuses_to_delete_a_supplier_with_purchases()
    {
        $this->expectException(QueryException::class);
        $this->supplier->delete();
    }

    /** @test */
    public function purchases_without_supplier_still_display()
    {
        // Achats importés ou détachés avant l'archivage : sans fournisseur
        $this->purchase->forceFill(['supplier_id' => null])->save();

        $this->get(route('admin.purchases.show', $this->purchase->id))->assertOk()->assertSee(__('documents.not_specified'));
        $this->get(route('admin.purchases.pdf', $this->purchase->id))->assertOk();
        $this->getJson(route('admin.purchases.get-purchases-api'))->assertOk()->assertJsonPath('data.0.supplier', null);
    }

    /** @test */
    public function an_archived_supplier_is_kept_on_the_purchase_it_already_has()
    {
        $draft = Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'state' => 'Draft']);
        $this->supplier->archive();

        // Il n'est plus proposé pour un nouvel achat, mais le brouillon existant le garde
        $this->get(route('admin.purchases.create'))->assertOk()->assertDontSee('Tissus Mada');
        $this->get(route('admin.purchases.edit', $draft->id))->assertOk()->assertSee('Tissus Mada');
    }
}
