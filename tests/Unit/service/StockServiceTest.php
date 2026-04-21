<?php

namespace Tests\Feature\service;

use App\Models\Product;
use App\Services\StockService;
use App\Models\ProductColor;
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
        $product = Product::factory()->create();
        $variant = ProductColor::create([
            'product_id' => $product->id,
            'color_id' => \App\Models\Color::factory()->create()->id,
            'stock' => 10
        ]);

        // Act
        $this->service->addStock($variant->id, 5, 'Test Add');

        // Assert
        $variant->refresh();
        $this->assertEquals(15, $variant->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $variant->id,
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
        $product = Product::factory()->create();
        $variant = ProductColor::create([
            'product_id' => $product->id,
            'color_id' => \App\Models\Color::factory()->create()->id,
            'stock' => 10
        ]);

        // Act
        $this->service->removeStock($variant->id, 3, 'Test Remove');

        // Assert
        $variant->refresh();
        $this->assertEquals(7, $variant->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_color_id' => $variant->id,
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
        $product = Product::factory()->create(['name' => 'Test Product']);
        $color = \App\Models\Color::factory()->create(['name' => 'Rouge']);
        $variant = ProductColor::create(['product_id' => $product->id, 'color_id' => $color->id, 'stock' => 5]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stock insuffisant pour la variante Test Product (Rouge).");

        // Act
        try {
            $this->service->removeStock($variant->id, 10, 'Sortie impossible');
        } finally {
            // Assert state after exception
            $this->assertEquals(5, $variant->fresh()->stock);
            $this->assertDatabaseMissing('stock_movements', [
                'reason' => 'Failed Remove'
            ]);
        }
    }

    /** @test */
    public function it_can_get_all_stock_movements_with_filters()
    {
        // Arrange
        $variant1 = ProductColor::create(['product_id' => Product::factory()->create(['name' => 'Laptop'])->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 10]);
        $variant2 = ProductColor::create(['product_id' => Product::factory()->create(['name' => 'Mouse'])->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 10]);

        $this->service->createStockMovement(['product_color_id' => $variant1->id, 'quantity' => 5, 'type' => 'in', 'reason' => 'Purchase ABC', 'stock_before' => 5, 'stock_after' => 10]);
        $this->travelTo(now()->subDay());
        $this->service->createStockMovement(['product_color_id' => $variant2->id, 'quantity' => -2, 'type' => 'out', 'reason' => 'Sale XYZ', 'stock_before' => 10, 'stock_after' => 8]);
        $this->travelTo(now()->subDays(4));
        $this->service->createStockMovement(['product_color_id' => $variant1->id, 'quantity' => -1, 'type' => 'out', 'reason' => 'Sale 123', 'stock_before' => 10, 'stock_after' => 9]);
        $this->travelBack();

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
    public function it_can_get_rotation_stats()
    {
        // Arrange
        $v1 = ProductColor::create(['product_id' => Product::factory()->create()->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 10]);
        $v2 = ProductColor::create(['product_id' => Product::factory()->create()->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 10]);
        $v3 = ProductColor::create(['product_id' => Product::factory()->create()->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 10]);

        $this->service->createStockMovement(['product_color_id' => $v1->id, 'type' => 'out', 'quantity' => -10, 'stock_before' => 20, 'stock_after' => 10, 'reason' => 's1']);
        $this->service->createStockMovement(['product_color_id' => $v1->id, 'type' => 'out', 'quantity' => -5, 'stock_before' => 10, 'stock_after' => 5, 'reason' => 's2']);
        $this->service->createStockMovement(['product_color_id' => $v2->id, 'type' => 'out', 'quantity' => -2, 'stock_before' => 10, 'stock_after' => 8, 'reason' => 's3']);
        $this->service->createStockMovement(['product_color_id' => $v3->id, 'type' => 'in', 'quantity' => 20, 'stock_before' => 0, 'stock_after' => 20, 'reason' => 'in']);

        // Act
        $stats = $this->service->getRotationStats(2);

        // Assert
        $this->assertCount(2, $stats);
        $this->assertEquals($v1->id, $stats[0]->product_color_id);
        $this->assertEquals(15, $stats[0]->total_out);
        $this->assertEquals($v2->id, $stats[1]->product_color_id);
        $this->assertEquals(2, $stats[1]->total_out);
    }

    /** @test */
    public function it_can_get_stock_evolution_for_variant()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductColor::create(['product_id' => $product->id, 'color_id' => \App\Models\Color::factory()->create()->id, 'stock' => 0]);

        $date1 = now()->subDays(2)->startOfDay();
        $date2 = now()->subDays(1)->startOfDay();
        $date3 = now()->startOfDay();

        $this->travelTo($date1);
        $this->service->addStock($variant->id, 10, 'Initial Stock');

        $this->travelTo($date2);
        $this->service->removeStock($variant->id, 3, 'Sale');

        $this->travelTo($date3);
        $this->service->addStock($variant->id, 5, 'Restock');

        $this->travelTo($date3->copy()->addHours(1));
        $finalDate = now();
        $variant->refresh();

        // Act
        $evolution = $this->service->getStockEvolutionForVariant($variant->id);
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
