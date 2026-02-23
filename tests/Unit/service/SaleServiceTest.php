<?php

namespace Tests\Feature\service;

use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaleService $service;
    protected MockInterface $stockServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // On mock le StockService pour isoler nos tests de SaleService
        $this->stockServiceMock = Mockery::mock(StockService::class);
        $this->app->instance(StockService::class, $this->stockServiceMock);

        // On injecte le mock dans notre service
        $this->service = new SaleService($this->stockServiceMock);
    }

    /** @test */
    public function it_can_create_a_sale_and_update_stock()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00, 'quantity_stock' => 50]);
        $productsData = [
            ['product_id' => $product->id, 'quantity' => 2]
        ];
        $discount = 10.00;

        // On s'attend à ce que la méthode removeStock soit appelée une fois avec les bons arguments
        $this->stockServiceMock
            ->shouldReceive('removeStock')
            ->once()
            ->with($product->id, 2, Mockery::on(function ($reason) {
                return str_starts_with($reason, 'Vente SALE-');
            }));

        // Act
        $sale = $this->service->createSale($productsData, $discount);

        // Assert
        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'total_brut' => 200.00, // 2 * 100.00
            'discount' => 10.00,
            'total_net' => 190.00, // 200 - 10
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_stock_is_insufficient()
    {
        // Arrange
        $product = Product::factory()->create(['quantity_stock' => 1]);
        $productsData = [
            ['product_id' => $product->id, 'quantity' => 2]
        ];

        // On s'assure que la méthode removeStock n'est JAMAIS appelée
        $this->stockServiceMock->shouldNotReceive('removeStock');

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stock insuffisant pour : {$product->name}");

        $this->service->createSale($productsData);
    }

    /** @test */
    public function it_can_get_sales_statistics()
    {
        // Arrange: Create sales with specific data
        Sale::factory()->create(['created_at' => today(), 'total_net' => 100, 'discount' => 10]);
        Sale::factory()->create(['created_at' => today(), 'total_net' => 150, 'discount' => 0]);
        Sale::factory()->create(['created_at' => now()->subDays(2), 'total_net' => 200, 'discount' => 20]);

        // Act
        $stats = $this->service->getSalesStatistics();

        // Assert
        $this->assertEquals(2, $stats['today_sales_count']);
        $this->assertEquals(450.00, $stats['total_revenue']); // 100 + 150 + 200
        $this->assertEquals(150.00, $stats['average_sale']); // 450 / 3
        $this->assertEquals(30.00, $stats['total_discount']); // 10 + 20
    }

    /** @test */
    public function it_can_get_a_sale_by_id()
    {
        $sale = Sale::factory()->hasItems(1)->create();

        $foundSale = $this->service->getSaleById($sale->id);

        $this->assertEquals($sale->id, $foundSale->id);
        $this->assertCount(1, $foundSale->items);
    }
}
