<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Encaissement depuis l'écran de caisse : la vente est envoyée en AJAX (JSON) et la caisse
 * reçoit la monnaie à rendre, l'adresse du reçu et le stock restant.
 */
class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $variant;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $product = Product::factory()->create(['name' => 'Postiche lisse', 'category_id' => Category::factory()->create()->id]);
        $this->variant = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 5, 'price' => 1000,
        ]);
        $this->seller = User::factory()->frontOffice()->create();
    }

    private function cart(int $quantity = 2): array
    {
        return ['products' => [['product_color_id' => $this->variant->id, 'quantity' => $quantity]]];
    }

    /** @test */
    public function a_cash_sale_returns_the_change_the_receipt_and_the_remaining_stock()
    {
        $response = $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(2) + ['payment_method' => 'cash', 'amount_received' => 5000])
            ->assertCreated();

        $sale = Sale::firstOrFail();
        $response->assertJson([
            'reference' => $sale->reference,
            'total_net' => 2000,
            'amount_received' => 5000,
            'change_due' => 3000,
            'receipt_url' => route('saler.receipt', $sale->id),
            'stocks' => [$this->variant->id => 3],
        ]);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertEquals(5000, $sale->amount_received);
    }

    /** @test */
    public function cash_below_the_amount_due_is_refused_and_nothing_is_recorded()
    {
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(2) + ['payment_method' => 'cash', 'amount_received' => 1500])
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Montant reçu insuffisant'));

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    /** @test */
    public function the_amount_due_is_checked_after_the_discount()
    {
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(2) + ['discount' => 500, 'amount_received' => 1500])
            ->assertCreated()
            ->assertJson(['total_net' => 1500, 'change_due' => 0]);
    }

    /** @test */
    public function without_an_amount_received_the_customer_paid_the_exact_amount()
    {
        $this->actingAs($this->seller)->postJson(route('saler.store'), $this->cart(2))->assertCreated();

        $sale = Sale::firstOrFail();
        $this->assertSame('cash', $sale->payment_method);
        $this->assertEquals(2000, $sale->amount_received);
        $this->assertEquals(0, $sale->change_due);
    }

    /** @test */
    public function a_mobile_money_sale_is_recorded_for_the_exact_amount()
    {
        // Le montant saisi n'a pas de sens hors espèces : il est ignoré
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(2) + ['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP261026.1530.A12345', 'amount_received' => 10])
            ->assertCreated()
            ->assertJson(['payment_method' => 'mobile_money', 'amount_received' => 2000, 'change_due' => 0]);
    }

    /** @test */
    public function an_unknown_payment_method_is_rejected()
    {
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart() + ['payment_method' => 'cheque'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_method');
    }

    /** @test */
    public function insufficient_stock_is_reported_to_the_checkout()
    {
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(6))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Stock insuffisant'));
    }

    /** @test */
    public function the_back_office_checkout_points_to_the_back_office_receipt()
    {
        $this->actingAs(User::factory()->backOffice()->create())
            ->postJson(route('admin.sales.store'), $this->cart())
            ->assertCreated()
            ->assertJsonPath('receipt_url', route('admin.sales.receipt', Sale::firstOrFail()->id));
    }

    /** @test */
    public function the_sale_keeps_the_currency_configured_when_it_was_made()
    {
        settings()->updateSettings(['currency_symbol' => 'Ar']);

        $this->actingAs($this->seller)->postJson(route('saler.store'), $this->cart())->assertCreated();

        $this->assertSame('Ar', Sale::firstOrFail()->currency_symbol);
    }

    /** @test */
    public function the_receipt_lists_the_items_the_payment_and_the_change()
    {
        $this->actingAs($this->seller)
            ->postJson(route('saler.store'), $this->cart(2) + ['amount_received' => 5000])
            ->assertCreated();
        $sale = Sale::firstOrFail();

        $this->get(route('saler.receipt', $sale->id))
            ->assertOk()
            ->assertSee($sale->reference)
            ->assertSee('Postiche lisse')
            ->assertSee($this->seller->name)
            ->assertSeeInOrder([__('pos.received'), '5,000', __('pos.change'), '3,000']);
    }

    /** @test */
    public function a_seller_cannot_open_another_sellers_receipt()
    {
        $sale = Sale::factory()->create(['user_id' => User::factory()->frontOffice()->create()->id]);

        $this->actingAs($this->seller)->get(route('saler.receipt', $sale->id))->assertNotFound();
        $this->actingAs(User::factory()->backOffice()->create())->get(route('admin.sales.receipt', $sale->id))->assertOk();
    }
}
