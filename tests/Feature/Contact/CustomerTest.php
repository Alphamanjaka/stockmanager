<?php

namespace Tests\Feature\Contact;

use App\Core\Services\GlobalSearchService;
use App\Models\User;
use App\Modules\Contact\Models\Customer;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function a_customer_can_be_created_and_updated()
    {
        $this->post(route('admin.customers.store'), ['name' => 'Rakoto', 'phone' => '034 00 000 00'])
            ->assertSessionHasNoErrors();

        $customer = Customer::where('name', 'Rakoto')->firstOrFail();
        $this->assertSame('034 00 000 00', $customer->phone);

        $this->put(route('admin.customers.update', $customer->id), ['name' => 'Rakoto Jean', 'email' => 'jean@example.com'])
            ->assertRedirect(route('admin.customers.show', $customer->id));

        $this->assertSame('Rakoto Jean', $customer->fresh()->name);
        $this->assertSame('jean@example.com', $customer->fresh()->email);
    }

    /** @test */
    public function a_customer_needs_a_name_and_a_valid_email()
    {
        $this->post(route('admin.customers.store'), ['name' => '', 'email' => 'pas-un-email'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseCount('customers', 0);
    }

    /** @test */
    public function a_customer_with_sales_is_archived_and_keeps_their_sales()
    {
        $customer = Customer::factory()->create();
        $sale = Sale::factory()->create(['customer_id' => $customer->id]);

        $this->post(route('admin.customers.archive'), ['ids' => [$customer->id]])->assertSessionHas('success');
        $this->get(route('admin.customers.show', $customer->id))->assertOk()->assertSee('archived');

        // Suppression définitive refusée : la vente garde son client
        $this->delete(route('admin.customers.destroy', $customer->id))->assertSessionHas('error');
        $this->assertModelExists($customer);
        $this->assertSame($customer->id, $sale->fresh()->customer_id);
    }

    /** @test */
    public function customers_can_be_searched_by_name_or_phone()
    {
        Customer::factory()->create(['name' => 'Rasoa', 'phone' => '032 11 111 11']);
        Customer::factory()->create(['name' => 'Rabe', 'phone' => '033 22 222 22']);

        $this->get(route('admin.customers.index', ['search' => '032 11']))
            ->assertOk()->assertSee('Rasoa')->assertDontSee('Rabe');

        $names = app(GlobalSearchService::class)->search('Rabe')->where('type', 'customer')->pluck('name');
        $this->assertSame(['Rabe (033 22 222 22)'], $names->all());
    }

    /** @test */
    public function sellers_cannot_manage_customers_in_the_back_office()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->get(route('admin.customers.index'))->assertRedirect(route('dashboard'));
    }
}
