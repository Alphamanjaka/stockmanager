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
 * Paiement Mobile Money : l'opérateur et le numéro de transaction sont enregistrés sur la vente,
 * affichés sur le reçu et la facture, et permettent de retrouver la vente pour le rapprochement.
 */
class MobileMoneyTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $variant;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $product = Product::factory()->create(['name' => 'Postiche lisse', 'category_id' => Category::factory()->create()->id]);
        $this->variant = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 10, 'price' => 1000,
        ]);
        $this->seller = User::factory()->frontOffice()->create();
    }

    private function checkout(array $payment)
    {
        return $this->actingAs($this->seller)->postJson(route('saler.store'), $payment + [
            'products' => [['product_color_id' => $this->variant->id, 'quantity' => 1]],
        ]);
    }

    /** @test */
    public function the_operator_and_the_transaction_number_are_recorded()
    {
        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'orange_money', 'payment_reference' => '  PP261026.1530.B98765 '])
            ->assertCreated()
            ->assertJson(['payment_operator' => 'orange_money', 'payment_reference' => 'PP261026.1530.B98765']);

        $sale = Sale::firstOrFail();
        $this->assertSame('orange_money', $sale->payment_operator);
        $this->assertSame('PP261026.1530.B98765', $sale->payment_reference);
        $this->assertSame('Mobile Money · Orange Money', $sale->paymentLabel());
    }

    /** @test */
    public function a_mobile_money_sale_requires_a_known_operator_and_a_transaction_number()
    {
        $this->checkout(['payment_method' => 'mobile_money'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_operator', 'payment_reference']);

        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'paypal', 'payment_reference' => 'X1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_operator');

        $this->assertSame(0, Sale::count());
    }

    /** @test */
    public function a_transaction_number_pays_only_one_sale_per_operator()
    {
        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP1'])->assertCreated();

        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_reference');

        // Même numéro chez un autre opérateur : une autre transaction
        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'airtel_money', 'payment_reference' => 'MP1'])->assertCreated();

        $this->assertSame(2, Sale::count());
    }

    /** @test */
    public function the_operator_is_not_kept_for_other_payment_methods()
    {
        $this->checkout(['payment_method' => 'cash', 'payment_operator' => 'mvola', 'payment_reference' => 'MP2'])->assertCreated();

        $sale = Sale::firstOrFail();
        $this->assertNull($sale->payment_operator);
        $this->assertNull($sale->payment_reference);
        $this->assertSame(__('pos.methods.cash'), $sale->paymentLabel());
    }

    /** @test */
    public function the_receipt_and_the_invoice_show_the_transaction()
    {
        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP261026.0901.C55555'])->assertCreated();
        $sale = Sale::firstOrFail();

        $this->get(route('saler.receipt', $sale->id))->assertOk()->assertSee('MVola')->assertSee('MP261026.0901.C55555');
        $this->get(route('saler.show', $sale->id))->assertOk()->assertSee('Mobile Money · MVola')->assertSee('MP261026.0901.C55555');
    }

    /** @test */
    public function the_back_office_finds_a_sale_by_its_transaction_number()
    {
        $this->checkout(['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP-FIND-ME'])->assertCreated();
        $this->checkout(['payment_method' => 'cash'])->assertCreated();
        [$mobile, $cash] = Sale::orderBy('id')->get()->all();

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.sales.index', ['search' => 'FIND-ME']))
            ->assertOk()
            ->assertSee($mobile->reference)
            ->assertDontSee($cash->reference);
    }
}
