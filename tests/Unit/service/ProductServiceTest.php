<?php

namespace Tests\Feature\service;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductService();
    }

    /** @test */
    public function it_can_get_all_products_paginated()
    {
        Product::factory()->count(20)->create();

        $result = $this->service->getAllProducts();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default per_page is 15
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $data = [
            'name' => 'New Awesome Product',
            'price' => 199.99,
            'quantity_stock' => 100,
            'alert_stock' => 10,
        ];

        $product = $this->service->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['name' => 'New Awesome Product']);
    }

    /** @test */
    public function it_can_get_a_product_by_id()
    {
        $createdProduct = Product::factory()->create();

        $foundProduct = $this->service->getProductById($createdProduct->id);

        $this->assertEquals($createdProduct->id, $foundProduct->id);
        $this->assertEquals($createdProduct->name, $foundProduct->name);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create(['name' => 'Old Name']);
        $newData = ['name' => 'New Updated Name', 'price' => 123.45];

        $this->service->updateProduct($product->id, $newData);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Updated Name',
            'price' => 123.45,
        ]);
    }

    /** @test */
    public function it_can_delete_a_product_without_relations()
    {
        $product = Product::factory()->create();

        $this->service->deleteProduct($product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_product_with_sales()
    {
        $product = Product::factory()->create();
        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'product_id' => $product->id,
            'sale_id' => $sale->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Impossible de supprimer ce produit car il est lié à des ventes ou des achats existants.");

        $this->service->deleteProduct($product->id);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_can_get_low_stock_products()
    {
        Product::factory()->create(['quantity_stock' => 5, 'alert_stock' => 10]); // Low stock
        Product::factory()->create(['quantity_stock' => 15, 'alert_stock' => 10]); // Enough stock

        $lowStockProducts = $this->service->getLowStockProducts();

        $this->assertCount(1, $lowStockProducts);
        $this->assertTrue($lowStockProducts->first()->quantity_stock < $lowStockProducts->first()->alert_stock);
    }

    /** @test */
    public function it_can_filter_products_by_name_and_category()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->create(['name' => 'Apple iPhone', 'category_id' => $category1->id]);
        Product::factory()->create(['name' => 'Samsung Galaxy', 'category_id' => $category2->id]);
        Product::factory()->create(['name' => 'Apple MacBook', 'category_id' => $category1->id]);

        // Filter by search
        $results = $this->service->getAllProducts(['search' => 'Apple']);
        $this->assertCount(2, $results);

        // Filter by category
        $results = $this->service->getAllProducts(['category' => $category2->id]);
        $this->assertCount(1, $results);
        $this->assertEquals('Samsung Galaxy', $results->first()->name);
    }

    /** @test */
    public function it_can_get_most_and_least_sold_products()
    {
        $product1 = Product::factory()->create(); // most sold
        $product2 = Product::factory()->create(); // least sold
        $product3 = Product::factory()->create(); // never sold

        $sale = Sale::factory()->create();

        SaleItem::factory()->create(['product_id' => $product1->id, 'sale_id' => $sale->id, 'quantity' => 10]);
        SaleItem::factory()->create(['product_id' => $product2->id, 'sale_id' => $sale->id, 'quantity' => 2]);

        $mostSold = $this->service->getMostSoldProduct();
        $leastSold = $this->service->getLeastSoldProduct();

        $this->assertEquals($product1->id, $mostSold->product_id);
        $this->assertEquals(10, $mostSold->total_sold);

        $this->assertEquals($product2->id, $leastSold->product_id);
        $this->assertEquals(2, $leastSold->total_sold);
    }
}
