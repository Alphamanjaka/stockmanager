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
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bon de commande (création et modification d'un brouillon) : en-tête, lignes, remise,
 * et action choisie (brouillon, commandé, réceptionné). Le stock n'augmente qu'à la réception.
 */
class PurchaseOrderFormTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private ProductColor $wax;

    private ProductColor $velours;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->supplier = Supplier::factory()->create();
        $this->wax = $this->variant('Wax', 2, 700);
        $this->velours = $this->variant('Velours', 0, 1500);
    }

    private function variant(string $name, int $stock, float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'alert_stock' => 5, 'price' => $cost * 2, 'price_purchase' => $cost,
        ]);
    }

    private function order(array $overrides = []): array
    {
        return array_merge([
            'supplier_id' => $this->supplier->id,
            'supplier_reference' => 'FAC-2026-118',
            'notes' => 'Livraison mardi',
            'discount' => 500,
            'products' => [
                ['product_color_id' => $this->wax->id, 'quantity' => 10, 'unit_price' => 700],
                ['product_color_id' => $this->velours->id, 'quantity' => 4, 'unit_price' => 1500],
            ],
        ], $overrides);
    }

    /** @test */
    public function a_draft_keeps_the_header_lines_and_discount_without_touching_the_stock()
    {
        $response = $this->post(route('admin.purchases.store'), $this->order() + ['action' => 'draft']);

        $purchase = Purchase::sole();
        $response->assertRedirect(route('admin.purchases.show', $purchase->id))
            ->assertSessionHas('success', "L'achat {$purchase->reference} est enregistré en brouillon. Le stock augmentera à sa réception.");

        $this->assertSame(['Draft', 'FAC-2026-118', 'Livraison mardi'], [$purchase->state, $purchase->supplier_reference, $purchase->notes]);
        $this->assertEquals([13000, 500, 12500], [$purchase->total_amount, $purchase->discount, $purchase->total_net]);
        $this->assertSame(2, $purchase->items()->count());
        $this->assertSame(2, $this->wax->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function ordering_changes_the_status_only()
    {
        $this->post(route('admin.purchases.store'), $this->order() + ['action' => 'ordered'])->assertSessionHas('success');

        $this->assertSame('Ordered', Purchase::sole()->state);
        $this->assertSame(0, StockMovement::count());
    }

    /** @test */
    public function receiving_now_adds_every_line_to_the_stock()
    {
        $this->post(route('admin.purchases.store'), $this->order() + ['action' => 'received'])->assertSessionHas('success');

        $purchase = Purchase::sole();
        $this->assertSame('Received', $purchase->state);
        $this->assertSame(12, $this->wax->fresh()->stock);
        $this->assertSame(4, $this->velours->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $this->wax->id, 'type' => 'in', 'quantity' => 10, 'reason' => "Réception Achat #{$purchase->reference}",
        ]);
    }

    /** @test */
    public function invalid_orders_are_refused_and_nothing_is_recorded()
    {
        $store = fn (array $overrides) => $this->post(route('admin.purchases.store'), $this->order($overrides));

        $store(['supplier_id' => null])->assertSessionHasErrors('supplier_id');
        $store(['products' => []])->assertSessionHasErrors('products');
        $store(['discount' => 13001])->assertSessionHasErrors('discount');
        $store(['products' => [
            ['product_color_id' => $this->wax->id, 'quantity' => 1, 'unit_price' => 700],
            ['product_color_id' => $this->wax->id, 'quantity' => 2, 'unit_price' => 700],
        ]])->assertSessionHasErrors('products.1.product_color_id');
        $store(['products' => [['product_color_id' => $this->wax->id, 'quantity' => 0, 'unit_price' => 700]]])
            ->assertSessionHasErrors('products.0.quantity');

        $this->assertSame(0, Purchase::count());
    }

    /** @test */
    public function a_draft_can_be_edited_and_received_in_one_step()
    {
        $this->post(route('admin.purchases.store'), $this->order());
        $purchase = Purchase::sole();

        $this->put(route('admin.purchases.update', $purchase->id), $this->order([
            'supplier_reference' => 'FAC-2026-119',
            'discount' => 0,
            'products' => [['product_color_id' => $this->wax->id, 'quantity' => 3, 'unit_price' => 650]],
        ]) + ['action' => 'received'])->assertRedirect(route('admin.purchases.show', $purchase->id));

        $purchase->refresh();
        $this->assertSame(['Received', 'FAC-2026-119'], [$purchase->state, $purchase->supplier_reference]);
        $this->assertEquals([1950, 0, 1950], [$purchase->total_amount, $purchase->discount, $purchase->total_net]);
        $this->assertSame(5, $this->wax->fresh()->stock);
        $this->assertSame(0, $this->velours->fresh()->stock);
    }

    /** @test */
    public function only_a_draft_can_be_edited()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'state' => 'Ordered']);

        $this->get(route('admin.purchases.edit', $purchase->id))
            ->assertRedirect(route('admin.purchases.show', $purchase->id))
            ->assertSessionHas('error');

        $this->put(route('admin.purchases.update', $purchase->id), $this->order())->assertSessionHas('error');
        $this->assertSame(0, $purchase->items()->count());
    }

    /** @test */
    public function the_form_proposes_the_purchase_price_and_the_last_price_paid_to_the_supplier()
    {
        // Coût proposé par défaut : le prix d'achat de la variante, jamais son prix de vente
        $this->get(route('admin.purchases.create'))->assertOk()
            ->assertSee('"id":'.$this->wax->id.',', false)
            ->assertSee('"cost":700', false)
            ->assertDontSee('"cost":1400', false);

        // Derniers prix payés à ce fournisseur : la ligne la plus récente l'emporte
        foreach ([680, 650] as $price) {
            $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
            PurchaseItem::create(['purchase_id' => $purchase->id, 'product_color_id' => $this->wax->id, 'quantity' => 1, 'unit_price' => $price, 'subtotal' => $price]);
        }

        $this->getJson(route('admin.purchases.supplier-prices', $this->supplier->id))
            ->assertOk()
            ->assertExactJson(['prices' => [(string) $this->wax->id => 650]]);

        $other = Supplier::factory()->create();
        $this->getJson(route('admin.purchases.supplier-prices', $other->id))->assertExactJson(['prices' => []]);
    }

    /** @test */
    public function shortages_of_a_supplier_are_offered_in_the_form()
    {
        // Wax (2 en stock, alerte à 5) a déjà été acheté à ce fournisseur : il est proposé dans ses ruptures
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'state' => 'Received']);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_color_id' => $this->wax->id, 'quantity' => 1, 'unit_price' => 690, 'subtotal' => 690]);

        $this->get(route('admin.purchases.create'))->assertOk()
            ->assertSee('"shortages":{"'.$this->supplier->id.'":[{"id":'.$this->wax->id, false);
    }
}
