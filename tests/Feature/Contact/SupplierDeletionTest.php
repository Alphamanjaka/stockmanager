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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un fournisseur ne se supprime pas facilement, et sa suppression n'efface jamais ses achats.
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
        $variant = ProductColor::create(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 5, 'price' => 1000]);
        PurchaseItem::create(['purchase_id' => $this->purchase->id, 'product_color_id' => $variant->id, 'quantity' => 5, 'unit_price' => 700, 'subtotal' => 3500]);
    }

    /** @test */
    public function deletion_requires_typing_the_exact_supplier_name()
    {
        foreach ([null, '', 'tissus mada', 'Tissus'] as $typed) {
            $this->delete(route('admin.suppliers.destroy', $this->supplier->id), ['confirm_name' => $typed])
                ->assertSessionHas('error');
        }

        $this->assertModelExists($this->supplier);
        $this->assertSame($this->supplier->id, $this->purchase->fresh()->supplier_id);
    }

    /** @test */
    public function deleting_a_supplier_keeps_its_purchases_without_supplier()
    {
        $this->delete(route('admin.suppliers.destroy', $this->supplier->id), ['confirm_name' => 'Tissus Mada'])
            ->assertRedirect(route('admin.suppliers.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($this->supplier);
        $this->assertModelExists($this->purchase);
        $this->assertNull($this->purchase->fresh()->supplier_id);
        $this->assertSame(1, $this->purchase->items()->count());
    }

    /** @test */
    public function the_database_itself_never_cascades_supplier_deletion_to_purchases()
    {
        // Suppression directe, sans passer par le service : la clé étrangère est en SET NULL
        $this->supplier->delete();

        $this->assertModelExists($this->purchase);
        $this->assertNull($this->purchase->fresh()->supplier_id);
    }

    /** @test */
    public function purchases_without_supplier_still_display()
    {
        $this->delete(route('admin.suppliers.destroy', $this->supplier->id), ['confirm_name' => 'Tissus Mada']);

        $this->get(route('admin.purchases.show', $this->purchase->id))->assertOk()->assertSee('Non renseigné');
        $this->get(route('admin.purchases.pdf', $this->purchase->id))->assertOk();
        $this->getJson(route('admin.purchases.get-purchases-api'))->assertOk()->assertJsonPath('data.0.supplier', null);
    }

    /** @test */
    public function the_supplier_page_shows_the_danger_zone()
    {
        $this->get(route('admin.suppliers.show', $this->supplier->id))
            ->assertOk()
            ->assertSee('Danger zone')
            ->assertSee('1 purchase(s)');
    }
}
