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
use App\Modules\Purchase\Services\PurchaseService;
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
            ->assertSee('name="items['.$variant->id.'][unit_price]" value="6000"', false)
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
            ->assertSee('name="items['.$variant->id.'][unit_price]" value="0"', false)
            ->assertDontSee('7,500.00');
    }

    /** @test */
    public function with_a_supplier_history_the_last_price_paid_is_proposed()
    {
        $variant = $this->shortage('crème bravia', 6000);
        $supplier = Supplier::factory()->create(['name' => 'Tissus Mada']);
        $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id, 'state' => 'Received']);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_color_id' => $variant->id, 'quantity' => 4, 'received_quantity' => 4, 'unit_price' => 5500, 'subtotal' => 22000]);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('Tissus Mada')
            ->assertSee('5,500.00')
            ->assertSee('Last purchase');
    }

    /** $received : quantité déjà livrée (par défaut : tout pour un achat reçu, rien sinon). */
    private function purchase(ProductColor $variant, string $state, int $quantity, ?Supplier $supplier = null, float $unitPrice = 5000, ?int $received = null): Purchase
    {
        $purchase = Purchase::factory()->create(['supplier_id' => ($supplier ?? Supplier::factory()->create())->id, 'state' => $state]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_color_id' => $variant->id, 'quantity' => $quantity,
            'received_quantity' => $received ?? ($state === Purchase::RECEIVED ? $quantity : 0),
            'unit_price' => $unitPrice, 'subtotal' => $quantity * $unitPrice,
        ]);

        return $purchase;
    }

    /** Ligne de rupture calculée pour une variante, ou null si elle n'est pas proposée. */
    private function suggestion(ProductColor $variant): ?object
    {
        return app(PurchaseService::class)->getShortageProductsGroupedBySupplier()
            ->flatMap(fn ($group) => $group['products'])
            ->firstWhere('id', $variant->id);
    }

    /** @test */
    public function the_quantity_already_ordered_is_deducted_from_the_suggestion()
    {
        $variant = $this->shortage('savon repardine', 6000);
        $this->purchase($variant, 'Ordered', 3);

        // Seuil 5 : remonter à 7,5, moins le stock (1) et la commande en cours (3)
        $suggestion = $this->suggestion($variant);
        $this->assertSame(3, $suggestion->ordered_quantity);
        $this->assertSame(4, $suggestion->suggested_quantity);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('On order')
            ->assertSee('Already ordered, not received');
    }

    /** @test */
    public function a_variant_covered_by_its_orders_is_not_suggested_again()
    {
        $variant = $this->shortage('savon repardine', 6000);
        $this->purchase($variant, 'Ordered', 2);
        $this->purchase($variant, 'Ordered', 8);

        $this->assertNull($this->suggestion($variant));
        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('No product requires restocking')
            ->assertDontSee('savon repardine');
    }

    /** @test */
    public function only_the_rest_of_a_partially_received_order_is_on_order()
    {
        $variant = $this->shortage('savon repardine', 6000);
        $this->purchase($variant, 'PartiallyReceived', 10, received: 7);
        // Commande soldée : le reste ne sera pas livré
        $this->purchase($variant, 'Received', 10, received: 4);

        // Seuil 5 : remonter à 7,5, moins le stock (1) et les 3 encore attendus
        $suggestion = $this->suggestion($variant);
        $this->assertSame(3, $suggestion->ordered_quantity);
        $this->assertSame(4, $suggestion->suggested_quantity);
    }

    /** @test */
    public function drafts_and_received_purchases_are_not_counted_as_on_order()
    {
        $variant = $this->shortage('savon repardine', 6000);
        $this->purchase($variant, 'Draft', 10);
        $this->purchase($variant, 'Received', 10);

        $suggestion = $this->suggestion($variant);
        $this->assertSame(0, $suggestion->ordered_quantity);
        $this->assertSame(7, $suggestion->suggested_quantity);
    }

    /** @test */
    public function the_last_supplier_is_taken_from_orders_not_drafts()
    {
        $variant = $this->shortage('crème bravia', 6000);
        $this->purchase($variant, 'Received', 1, Supplier::factory()->create(['name' => 'Tissus Mada']), 5500);
        $this->purchase($variant, 'Draft', 4, Supplier::factory()->create(['name' => 'Brouillon SARL']), 4000);

        $suggestion = $this->suggestion($variant);
        $this->assertSame('Tissus Mada', $suggestion->last_supplier_name);
        $this->assertEquals(5500, $suggestion->last_unit_price);
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
