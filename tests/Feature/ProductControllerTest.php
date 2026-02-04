<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // We create a user with 'back_office' role
        // Based on the context, this role is assumed to have access to product management
        $this->user = User::factory()->create([
            'role' => 'back_office',
        ]);
    }

    /**
     * Test that the products list is displayed (Index).
     */
    public function test_index_displays_products_list(): void
    {
        // Let's create some products in the database to test the index view
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products');
    }

    /**
     *Test that the create product form is displayed (Create).
     */
    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.products.create'));

        $response->assertStatus(200);
        $response->assertViewIs('products.create');
    }

    /**
     * Test that a new product is created (Store).
     */
    public function test_store_creates_new_product(): void
    {
        // We prepare the product data using the factory, but we don't save it to the database
        $productData = Product::factory()->make()->toArray();

        $response = $this->actingAs($this->user)->post(route('admin.products.store'), $productData);

        // we expect to be redirected to the products index with a success message in the session
        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');

        // we check that the product was actually created in the database with the correct data
        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'price' => $productData['price'],
        ]);
    }

    /**
     * Test that the store method validates the input data (Store).
     */
    public function test_store_validates_input(): void
    {
        // We try to create a product with empty data, which should trigger validation errors

        $response = $this->actingAs($this->user)->post(route('admin.products.store'), []);

        // we expect to be redirected back to the create form with validation errors in the session
        $response->assertSessionHasErrors(['name', 'price']);
    }

    /**
     *Test that the product details are displayed (Show).
     */
    public function test_show_displays_product_details(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)->get(route('admin.products.show', $product));

        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        $response->assertViewHas('product', function ($viewProduct) use ($product) {
            return $viewProduct->id === $product->id;
        });
    }

    /**
     *Test that the edit product form is displayed (Edit).
     */
    public function test_edit_displays_form(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)->get(route('admin.products.edit', $product));

        $response->assertStatus(200);
        $response->assertViewIs('products.edit');
        $response->assertViewHas('product');
    }

    /**
     * Test that a product is updated (Update).
     */
    public function test_update_modifies_product(): void
    {
        $product = Product::factory()->create();

        // we prepare new data for the product, we can use the factory to generate realistic data, but we change some fields to ensure that the update is actually happening
        $newData = array_merge($product->toArray(), [
            'name' => 'Unmodified Product Name',
            'price' => 150.00,
        ]);

        $response = $this->actingAs($this->user)->put(route('admin.products.update', $product), $newData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Unmodified Product Name',
            'price' => 150.00,
        ]);
    }

    /**
     * test that a product is deleted (Destroy).
     */
    public function test_destroy_deletes_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
