<?php

namespace Tests\Feature\service;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockService();
    }

    /** @test */
    public function it_can_add_stock_and_create_movement()
    {
        // Arrange
        $product = Product::factory()->create(['quantity_stock' => 10]);

        // Act
        $this->service->addStock($product->id, 5, 'Test Add');

        // Assert
        $product->refresh();
        $this->assertEquals(15, $product->quantity_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => 5,
            'type' => 'in',
            'reason' => 'Test Add',
            'stock_before' => 10,
            'stock_after' => 15,
        ]);
    }

    /** @test */
    public function it_can_remove_stock_and_create_movement()
    {
        // Arrange
        $product = Product::factory()->create(['quantity_stock' => 10]);

        // Act
        $this->service->removeStock($product->id, 3, 'Test Remove');

        // Assert
        $product->refresh();
        $this->assertEquals(7, $product->quantity_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => -3,
            'type' => 'out',
            'reason' => 'Test Remove',
            'stock_before' => 10,
            'stock_after' => 7,
        ]);
    }

    /** @test */
    public function it_throws_exception_when_removing_insufficient_stock()
    {
        // Arrange
        $product = Product::factory()->create(['quantity_stock' => 5]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not enough stock for this product.');

        // Act
        try {
            $this->service->removeStock($product->id, 10, 'Failed Remove');
        } finally {
            // Assert state after exception
            $this->assertEquals(5, $product->fresh()->quantity_stock);
            $this->assertDatabaseMissing('stock_movements', [
                'reason' => 'Failed Remove'
            ]);
        }
    }

    /** @test */
    public function it_can_get_all_stock_movements_with_filters()
    {
        // Arrange
        $product1 = Product::factory()->create(['name' => 'Laptop']);
        $product2 = Product::factory()->create(['name' => 'Mouse']);

        StockMovement::factory()->create(['product_id' => $product1->id, 'type' => 'in', 'reason' => 'Purchase ABC', 'created_at' => now()]);
        StockMovement::factory()->create(['product_id' => $product2->id, 'type' => 'out', 'reason' => 'Sale XYZ', 'created_at' => now()->subDay()]);
        StockMovement::factory()->create(['product_id' => $product1->id, 'type' => 'out', 'reason' => 'Sale 123', 'created_at' => now()->subDays(5)]);

        // Test without filters
        $result = $this->service->getAllStockMovements();
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(3, $result->items());

        // Test with search filter
        $result = $this->service->getAllStockMovements(['search' => 'Sale']);
        $this->assertCount(2, $result->items());

        // Test with type filter
        $result = $this->service->getAllStockMovements(['type' => 'in']);
        $this->assertCount(1, $result->items());
        $this->assertEquals('in', $result->first()->type);

        // Test with date filter
        $result = $this->service->getAllStockMovements(['date_from' => now()->subDays(2)->startOfDay(), 'date_to' => now()->endOfDay()]);
        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_can_get_dormant_products()
    {
        // Arrange
        // 1. Dormant product: has stock, no recent 'out' movement
        $dormantProduct = Product::factory()->create(['quantity_stock' => 10]);
        StockMovement::factory()->create([
            'product_id' => $dormantProduct->id,
            'type' => 'out',
            'created_at' => now()->subDays(70)
        ]);

        // 2. Active product: has stock, has recent 'out' movement
        $activeProduct = Product::factory()->create(['quantity_stock' => 10]);
        StockMovement::factory()->create([
            'product_id' => $activeProduct->id,
            'type' => 'out',
            'created_at' => now()->subDays(10)
        ]);

        // 3. Out of stock product: should not be listed
        Product::factory()->create(['quantity_stock' => 0]);

        // Act
        $dormantProducts = $this->service->getDormantProducts(60);

        // Assert
        $this->assertCount(1, $dormantProducts);
        $this->assertEquals($dormantProduct->id, $dormantProducts->first()->id);
    }

    /** @test */
    public function it_can_get_rotation_stats()
    {
        // Arrange
        $product1 = Product::factory()->create(); // high rotation
        $product2 = Product::factory()->create(); // low rotation
        $product3 = Product::factory()->create(); // no rotation

        StockMovement::factory()->create(['product_id' => $product1->id, 'type' => 'out', 'quantity' => -10]);
        StockMovement::factory()->create(['product_id' => $product1->id, 'type' => 'out', 'quantity' => -5]);
        StockMovement::factory()->create(['product_id' => $product2->id, 'type' => 'out', 'quantity' => -2]);
        StockMovement::factory()->create(['product_id' => $product3->id, 'type' => 'in', 'quantity' => 20]); // 'in' should be ignored

        // Act
        $stats = $this->service->getRotationStats(2);

        // Assert
        $this->assertCount(2, $stats);
        $this->assertEquals($product1->id, $stats[0]->product_id);
        $this->assertEquals(15, $stats[0]->total_out);
        $this->assertEquals($product2->id, $stats[1]->product_id);
        $this->assertEquals(2, $stats[1]->total_out);
    }

    /** @test */
    public function it_can_get_stock_evolution_for_product()
    {
        // Arrange
        $product = Product::factory()->create(['quantity_stock' => 0]);
        $date1 = now()->subDays(2)->startOfDay();
        $date2 = now()->subDays(1)->startOfDay();
        $date3 = now()->startOfDay();

        $this->travelTo($date1);
        $this->service->addStock($product->id, 10, 'Initial Stock');

        $this->travelTo($date2);
        $this->service->removeStock($product->id, 3, 'Sale');

        $this->travelTo($date3);
        $this->service->addStock($product->id, 5, 'Restock');

        $this->travelTo($date3->copy()->addHours(1));
        $finalDate = now();
        $product->refresh();

        // Act
        $evolution = $this->service->getStockEvolutionForProduct($product->id);
        $this->travelBack();

        // Assert
        $this->assertCount(4, $evolution);

        $this->assertEquals(0, $evolution[0]['y']);
        $this->assertEquals($date1->toIso8601String(), $evolution[0]['x']);

        $this->assertEquals(10, $evolution[1]['y']);
        $this->assertEquals($date2->toIso8601String(), $evolution[1]['x']);

        $this->assertEquals(7, $evolution[2]['y']);
        $this->assertEquals($date3->toIso8601String(), $evolution[2]['x']);

        $this->assertEquals(12, $evolution[3]['y']);
        $this->assertEquals($finalDate->toIso8601String(), $evolution[3]['x']);
    }
}
