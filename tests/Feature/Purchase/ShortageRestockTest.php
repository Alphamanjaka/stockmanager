<?php

namespace Tests\Feature\Purchase;

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
 * Réapprovisionnement des ruptures : le coût proposé est un prix d'achat, jamais le prix de vente.
 * Dernier prix payé au fournisseur ; sans historique, prix d'achat de la fiche produit ; sinon à saisir.
 */
class ShortageRestockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** Variante en rupture (stock 1, seuil 5), vendue 10 000. */
    private function shortage(string $name, ?float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 1, 'alert_stock' => 5, 'price' => 10000, 'price_purchase' => $cost,
        ]);
    }

    /** @test */
    public function without_purchase_history_the_product_purchase_price_is_proposed()
    {
        $variant = $this->shortage('savon repardine', 6000);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('6,000.00')
            ->assertSee('Purchase price (product)')
            ->assertSee('name="items[' . $variant->id . '][unit_price]" value="6000"', false)
            // Plus jamais 75 % du prix de vente
            ->assertDontSee('7,500.00');
    }

    /** @test */
    public function an_unknown_cost_is_left_to_fill_in_on_the_order()
    {
        $variant = $this->shortage('gel inconnu', null);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('To fill in on the order')
            ->assertSee('name="items[' . $variant->id . '][unit_price]" value="0"', false)
            ->assertDontSee('7,500.00');
    }

    /** @test */
    public function with_a_supplier_history_the_last_price_paid_is_proposed()
    {
        $variant = $this->shortage('crème bravia', 6000);
        $supplier = Supplier::factory()->create(['name' => 'Tissus Mada']);
        $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id, 'state' => 'Received']);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_color_id' => $variant->id, 'quantity' => 4, 'unit_price' => 5500, 'subtotal' => 22000]);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('Tissus Mada')
            ->assertSee('5,500.00')
            ->assertSee('Last purchase');
    }

    /** @test */
    public function the_created_draft_order_uses_the_proposed_cost()
    {
        $variant = $this->shortage('savon repardine', 6000);

        $this->post(route('admin.purchases.storeFromShortage'), [
            'items' => [$variant->id => ['selected' => 1, 'supplier_id' => '', 'product_id' => $variant->id, 'unit_price' => 6000, 'quantity' => 7]],
        ])->assertRedirect(route('admin.purchases.index'));

        $item = PurchaseItem::where('product_color_id', $variant->id)->firstOrFail();
        $this->assertEquals(6000, $item->unit_price);
        $this->assertSame('Draft', Purchase::findOrFail($item->purchase_id)->state);
    }
}
