<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $frontOfficeUser;
    protected $backOfficeUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user with 'front_office' role for testing access to sales features
        $this->frontOfficeUser = User::factory()->create([
            'role' => 'front_office',
        ]);
        // Create a user with 'back_office' role to test unauthorized access
        $this->backOfficeUser = User::factory()->create([
            'role' => 'back_office',
        ]);
    }

    /**
     * Test     that the sales list is displayed (Index).
     */
    public function test_index_displays_sales_list(): void
    {
        // let's create some sales in the database to test the index view
        Sale::factory()->count(3)->create();

        $response = $this->actingAs($this->frontOfficeUser)->get(route('sales.index'));

        $response->assertStatus(200);
        $response->assertViewIs('sales.index');
        // We check that the view has the necessary data for displaying sales and statistics
        $response->assertViewHas('sales');
    }

    /**
     * test that the create sale form is displayed (Create).
     */
    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->frontOfficeUser)->get(route('sales.create'));

        $response->assertStatus(200);
        $response->assertViewIs('sales.create');
        $response->assertViewHas('products');
    }

    /**
     * Test that a new sale is created successfully (Store).
     */
    public function test_store_creates_new_sale(): void
    {
        // We create a product in the database to be able to create a sale with it
        $product = Product::factory()->create([
            'price' => 100.00,
            'quantity_stock' => 50
        ]);

        $saleData = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ],
            'discount' => 10,
        ];

        $response = $this->actingAs($this->frontOfficeUser)->post(route('sales.store'), $saleData);

        // we retrieve the latest sale from the database to check that it was created correctly
        $sale = Sale::latest()->first();

        // check    that we are redirected to the sale details page with a success message
        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionHas('success');

        //  we check that the sale was created in the database with the correct data
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'discount' => 10,
        ]);
    }

    /**
     * Test that the store method fails with invalid data (ex: empty products or non-existent product ID).
     */
    public function test_store_fails_with_invalid_data(): void
    {
        // Test with empty products array
        $this->actingAs($this->frontOfficeUser)
            ->post(route('sales.store'), ['products' => [], 'discount' => 0])
            ->assertSessionHasErrors('products');

        // Test with non-existent product ID
        $this->actingAs($this->frontOfficeUser)
            ->post(route('sales.store'), [
                'products' => [['product_id' => 999, 'quantity' => 1]],
                'discount' => 0
            ])
            ->assertSessionHasErrors('products.0.product_id');
    }

    /**
     * test that the sale details are displayed correctly (Show).
     */
    public function test_show_displays_sale_details(): void
    {
        $sale = Sale::factory()->create();

        $response = $this->actingAs($this->frontOfficeUser)->get(route('sales.show', $sale));

        $response->assertStatus(200);
        $response->assertViewIs('sales.show');
        $response->assertViewHas('sale');
    }

    /**
     * Test that the show method returns 404 for a non-existent sale.
     */
    public function test_show_returns_404_for_non_existent_sale(): void
    {
        $this->actingAs($this->frontOfficeUser)->get(route('sales.show', 999))
            ->assertNotFound();
    }

    /**
     * Test that the export PDF functionality works and returns a downloadable PDF file.
     */
    public function test_export_pdf_downloads_file(): void
    {
        $sale = Sale::factory()->create(['reference' => 'SALE-TEST-PDF']);

        $response = $this->actingAs($this->frontOfficeUser)->get(route('sales.pdf', $sale));

        $response->assertStatus(200);
        // We check that the response has the correct headers for a PDF file download with the expected filename
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=facture_SALE-TEST-PDF.pdf');
    }

    /**
     * TEst that guests are redirected to the login page when trying to access sales routes.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $sale = Sale::factory()->create();

        $this->get(route('sales.index'))->assertRedirect(route('login'));
        $this->get(route('sales.create'))->assertRedirect(route('login'));
        $this->post(route('sales.store'))->assertRedirect(route('login'));
        $this->get(route('sales.show', $sale))->assertRedirect(route('login'));
        $this->get(route('sales.pdf', $sale))->assertRedirect(route('login'));
    }

    /**
     * Test that users without the 'front_office' role are redirected when accessing sales routes.
     */
    public function test_unauthorized_users_are_forbidden(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs($this->backOfficeUser)->get(route('sales.index'))->assertRedirect();
        $this->actingAs($this->backOfficeUser)->get(route('sales.create'))->assertRedirect();
        $this->actingAs($this->backOfficeUser)->post(route('sales.store'))->assertRedirect();
        $this->actingAs($this->backOfficeUser)->get(route('sales.show', $sale))->assertRedirect();
        $this->actingAs($this->backOfficeUser)->get(route('sales.pdf', $sale))->assertRedirect();
    }

    /**
     * Test that the export PDF method returns 404 when trying to export a non-existent sale.
     */
    public function test_export_pdf_returns_404_for_non_existent_sale(): void
    {
        $this->actingAs($this->frontOfficeUser)->get(route('sales.pdf', 999))
            ->assertNotFound();
    }
}
