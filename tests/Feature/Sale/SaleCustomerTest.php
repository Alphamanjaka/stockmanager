<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Customer;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCustomerTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $this->variant = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 5, 'price' => 1000,
        ]);
    }

    private function lines(int $quantity = 1): array
    {
        return ['products' => [['product_color_id' => $this->variant->id, 'quantity' => $quantity]]];
    }

    /** @test */
    public function a_back_office_sale_can_be_linked_to_an_existing_customer()
    {
        $this->actingAs(User::factory()->backOffice()->create());
        $customer = Customer::factory()->create();

        $this->post(route('admin.sales.store'), $this->lines() + ['customer_id' => $customer->id])
            ->assertSessionHas('success');

        $this->assertSame($customer->id, Sale::firstOrFail()->customer_id);
    }

    /** @test */
    public function a_seller_can_create_the_customer_while_selling()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->post(route('saler.store'), $this->lines() + ['new_customer_name' => 'Rasoa', 'new_customer_phone' => '032 00 000 00'])
            ->assertSessionHas('success');

        $customer = Customer::where('name', 'Rasoa')->firstOrFail();
        $this->assertSame('032 00 000 00', $customer->phone);
        $this->assertSame($customer->id, Sale::firstOrFail()->customer_id);
    }

    /** @test */
    public function the_new_customer_is_not_kept_when_the_sale_fails()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        // 6 demandés pour 5 en stock : la vente échoue
        $this->post(route('saler.store'), $this->lines(6) + ['new_customer_name' => 'Rasoa'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('sales', 0);
    }

    /** @test */
    public function an_anonymous_sale_has_no_customer_and_an_unknown_customer_is_rejected()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->post(route('admin.sales.store'), $this->lines())->assertSessionHas('success');
        $this->assertNull(Sale::firstOrFail()->customer_id);

        $this->post(route('admin.sales.store'), $this->lines() + ['customer_id' => 999])
            ->assertSessionHasErrors('customer_id');
    }

    /** @test */
    public function the_sales_list_can_be_filtered_by_customer()
    {
        $this->actingAs(User::factory()->backOffice()->create());
        $rasoa = Customer::factory()->create(['name' => 'Rasoa']);
        Sale::factory()->create(['reference' => 'SALE-RASOA', 'customer_id' => $rasoa->id]);
        Sale::factory()->create(['reference' => 'SALE-OTHER']);

        $this->get(route('admin.sales.index', ['customer' => $rasoa->id]))
            ->assertOk()
            ->assertSee('SALE-RASOA')
            ->assertDontSee('SALE-OTHER');
    }
}
