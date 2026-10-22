<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Réception partielle : le fournisseur livre une partie de la commande, le reste reste attendu.
 * Chaque livraison fait entrer en stock ce qui est arrivé ; l'achat est « partiellement reçu »
 * jusqu'à la dernière livraison, ou jusqu'à ce que la commande soit soldée.
 */
class PartialReceptionTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $service;

    private ProductColor $wax;

    private ProductColor $velours;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->service = app(PurchaseService::class);
        $this->wax = $this->variant('Wax', 0, null);
        $this->velours = $this->variant('Velours', 0, null);
    }

    private function variant(string $name, int $stock, ?float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'price' => 5000, 'price_purchase' => $cost,
        ]);
    }

    /** Commande passée : 10 Wax à 1 000 et 5 Velours à 2 000, sans remise. */
    private function order(): Purchase
    {
        $purchase = $this->service->processPurchase(null, [
            ['product_color_id' => $this->wax->id, 'quantity' => 10, 'unit_price' => 1000],
            ['product_color_id' => $this->velours->id, 'quantity' => 5, 'unit_price' => 2000],
        ]);

        return $this->service->changeState($purchase, Purchase::ORDERED);
    }

    /** @return array<int, int> [id de ligne => quantité livrée] */
    private function delivery(Purchase $purchase, int $wax, int $velours): array
    {
        $lines = $purchase->items()->get()->keyBy('product_color_id');

        return [$lines[$this->wax->id]->id => $wax, $lines[$this->velours->id]->id => $velours];
    }

    private function received(Purchase $purchase, ProductColor $variant): int
    {
        return $purchase->items()->where('product_color_id', $variant->id)->value('received_quantity');
    }

    /** @test */
    public function a_partial_delivery_adds_only_the_delivered_quantities()
    {
        $purchase = $this->order();

        $purchase = $this->service->receive($purchase, $this->delivery($purchase, 6, 0));

        $this->assertSame(Purchase::PARTIALLY_RECEIVED, $purchase->fresh()->state);
        $this->assertSame(6, $this->wax->fresh()->stock);
        $this->assertSame(0, $this->velours->fresh()->stock);
        $this->assertSame(6, $this->received($purchase, $this->wax));
        $this->assertSame(9, $purchase->fresh()->remainingQuantity()); // 4 Wax et 5 Velours
        $this->assertSame(1, StockMovement::where('source_type', 'purchase')->where('source_id', $purchase->id)->count());
    }

    /** @test */
    public function the_last_delivery_completes_the_purchase()
    {
        $purchase = $this->order();
        $this->service->receive($purchase, $this->delivery($purchase, 6, 0));

        $this->service->receive($purchase, $this->delivery($purchase, 4, 5));

        $this->assertSame(Purchase::RECEIVED, $purchase->fresh()->state);
        $this->assertSame(10, $this->wax->fresh()->stock);
        $this->assertSame(5, $this->velours->fresh()->stock);
        $this->assertSame(0, $purchase->fresh()->remainingQuantity());
    }

    /** @test */
    public function a_delivery_larger_than_the_rest_is_refused_as_a_whole()
    {
        $purchase = $this->order();

        $thrown = null;
        try {
            // Velours valide, Wax au-delà de la commande : rien n'est enregistré
            $this->service->receive($purchase, $this->delivery($purchase, 11, 5));
        } catch (\Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertSame(Purchase::ORDERED, $purchase->fresh()->state);
        $this->assertSame(0, $this->wax->fresh()->stock);
        $this->assertSame(0, $this->velours->fresh()->stock);
        $this->assertSame(0, $this->received($purchase, $this->velours));
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** @test */
    public function an_empty_delivery_is_refused()
    {
        $purchase = $this->order();

        $this->expectException(\Exception::class);
        $this->service->receive($purchase, $this->delivery($purchase, 0, 0));
    }

    /** @test */
    public function each_delivery_averages_the_cost_with_its_own_quantity()
    {
        $variant = $this->variant('Gel', 10, 800);
        $purchase = $this->service->changeState($this->service->processPurchase(null, [
            ['product_color_id' => $variant->id, 'quantity' => 10, 'unit_price' => 1000],
        ]), Purchase::ORDERED);
        $line = $purchase->items()->value('id');

        $this->service->receive($purchase, [$line => 5]);
        $this->assertEqualsWithDelta(866.67, (float) $variant->fresh()->price_purchase, 0.01); // (10 × 800 + 5 × 1 000) / 15

        $this->service->receive($purchase, [$line => 5]);
        // Même coût qu'une réception d'un coup : (10 × 800 + 10 × 1 000) / 20
        $this->assertEqualsWithDelta(900, (float) $variant->fresh()->price_purchase, 0.01);
    }

    /** @test */
    public function receiving_everything_still_expected_adds_only_the_rest()
    {
        $purchase = $this->order();
        $this->service->receive($purchase, $this->delivery($purchase, 6, 0));

        $this->service->changeState($purchase, Purchase::RECEIVED);

        $this->assertSame(Purchase::RECEIVED, $purchase->fresh()->state);
        $this->assertSame(10, $this->wax->fresh()->stock);
        $this->assertSame(5, $this->velours->fresh()->stock);
    }

    /** @test */
    public function a_partially_received_purchase_cannot_go_back_to_draft_or_ordered()
    {
        $purchase = $this->order();
        $this->service->receive($purchase, $this->delivery($purchase, 6, 0));

        foreach ([Purchase::DRAFT, Purchase::ORDERED] as $state) {
            try {
                $this->service->changeState($purchase, $state);
                $this->fail("Retour à {$state} accepté");
            } catch (\Exception) {
                $this->assertSame(Purchase::PARTIALLY_RECEIVED, $purchase->fresh()->state);
            }
        }
    }

    /** @test */
    public function closing_the_order_stops_expecting_the_rest()
    {
        $ordered = $this->order();
        $this->assertSame(Purchase::ORDERED, $ordered->fresh()->state);

        // Rien de livré : on ne solde pas une commande, on la remet en brouillon ou on la supprime
        $this->expectExceptionOnce(fn () => $this->service->closeRemaining($ordered));

        $this->service->receive($ordered, $this->delivery($ordered, 6, 0));
        $this->service->closeRemaining($ordered);

        $purchase = $ordered->fresh();
        $this->assertSame(Purchase::RECEIVED, $purchase->state);
        $this->assertSame(6, $this->wax->fresh()->stock);
        $this->assertSame(6, $this->received($purchase, $this->wax));
        $this->assertSame(9, $purchase->remainingQuantity()); // non livré
        $this->assertEqualsWithDelta(6000, $purchase->receivedValue(), 0.01);

        // Statut final : plus aucune livraison
        $this->expectExceptionOnce(fn () => $this->service->receive($purchase, $this->delivery($purchase, 4, 0)));
    }

    /** @test */
    public function deleting_a_partially_received_purchase_takes_back_only_what_was_delivered()
    {
        $purchase = $this->order();
        $this->service->receive($purchase, $this->delivery($purchase, 6, 0));

        $this->service->deletePurchase($purchase->id);

        $this->assertModelMissing($purchase);
        $this->assertSame(0, $this->wax->fresh()->stock);
        $this->assertSame(0, $this->velours->fresh()->stock);
    }

    /** @test */
    public function the_delivery_page_records_a_partial_delivery()
    {
        $purchase = $this->order();

        $this->get(route('admin.purchases.receive', $purchase->id))
            ->assertOk()
            ->assertSee('Delivered now')
            ->assertSee('name="quantities['.$purchase->items()->first()->id.']"', false);

        $this->post(route('admin.purchases.receive.store', $purchase->id), ['quantities' => $this->delivery($purchase, 6, 0)])
            ->assertRedirect(route('admin.purchases.show', $purchase->id))
            ->assertSessionHas('success', 'Delivery recorded: 9 item(s) still to be delivered.');

        $this->get(route('admin.purchases.show', $purchase->id))
            ->assertOk()
            ->assertSee('Partially received')
            ->assertSee('To deliver')
            ->assertSee('Deliveries')
            ->assertSee('Close the order (rest not delivered)');

        $this->getJson(route('admin.purchases.get-purchases-api'))
            ->assertOk()
            ->assertJsonPath('data.0.state', Purchase::PARTIALLY_RECEIVED);

        // Au-delà du reste à livrer : refusé, message affiché sur la page
        $this->from(route('admin.purchases.receive', $purchase->id))
            ->post(route('admin.purchases.receive.store', $purchase->id), ['quantities' => $this->delivery($purchase, 5, 0)])
            ->assertRedirect(route('admin.purchases.receive', $purchase->id))
            ->assertSessionHas('error');

        $this->post(route('admin.purchases.close', $purchase->id))->assertRedirect(route('admin.purchases.show', $purchase->id));
        $this->assertSame(Purchase::RECEIVED, $purchase->fresh()->state);
        $this->get(route('admin.purchases.receive', $purchase->id))->assertRedirect(route('admin.purchases.show', $purchase->id));
    }

    /** @test */
    public function the_migration_marks_the_lines_of_received_purchases_as_fully_delivered()
    {
        $migration = require base_path('app/Modules/Purchase/Database/Migrations/2026_10_24_100000_add_partial_reception_to_purchases.php');
        $migration->down();

        // Avant la migration : achats reçus en entier, ou pas encore reçus
        $line = function (string $state) {
            $purchase = Purchase::factory()->create(['state' => $state]);
            DB::table('purchase_items')->insert([
                'purchase_id' => $purchase->id, 'product_color_id' => $this->wax->id,
                'quantity' => 7, 'unit_price' => 1000, 'subtotal' => 7000, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $purchase;
        };
        $received = $line(Purchase::RECEIVED);
        $ordered = $line(Purchase::ORDERED);

        $migration->up();

        $this->assertSame(7, $this->received($received, $this->wax));
        $this->assertSame(0, $this->received($ordered, $this->wax));
        $this->assertSame(0, $received->fresh()->remainingQuantity());
    }

    private function expectExceptionOnce(callable $action): void
    {
        try {
            $action();
        } catch (\Exception) {
            return;
        }

        $this->fail('Une exception était attendue');
    }
}
