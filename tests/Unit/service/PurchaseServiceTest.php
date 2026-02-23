<?php

namespace Tests\Feature\service;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseService $service;
    protected MockInterface $stockServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the StockService to isolate our tests
        $this->stockServiceMock = Mockery::mock(StockService::class);
        $this->app->instance(StockService::class, $this->stockServiceMock);

        // Inject the mock into our service
        $this->service = new PurchaseService($this->stockServiceMock);
    }

    /** @test */
    public function it_can_process_a_purchase_without_updating_stock()
    {
        // Arrange
        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $items = [
            ['product_id' => $product1->id, 'quantity' => 5, 'unit_price' => 10.00], // 50
            ['product_id' => $product2->id, 'quantity' => 2, 'unit_price' => 25.00], // 50
        ];

        // We ensure that the addStock method is NEVER called during processing
        $this->stockServiceMock->shouldNotReceive('addStock');

        // Act
        $purchase = $this->service->processPurchase($supplier->id, $items);

        // Assert
        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'total_amount' => 100.00,
            'total_net' => 100.00,
            'state' => 'Draft', // Default state
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'unit_price' => 10.00,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'unit_price' => 25.00,
        ]);
    }

    /** @test */
    public function it_can_mark_a_purchase_as_received_and_update_stock()
    {
        // Arrange
        $purchase = Purchase::factory()
            ->hasItems(2, function (array $attributes, Purchase $purchase) {
                return ['product_id' => Product::factory()->create()->id];
            })
            ->create(['state' => 'Ordered']);

        // We expect addStock to be called for each item in the purchase
        foreach ($purchase->items as $item) {
            $this->stockServiceMock
                ->shouldReceive('addStock')
                ->once()
                ->with($item->product_id, $item->quantity, "Réception Achat #{$purchase->reference}");
        }

        // Act
        $this->service->markAsReceived($purchase);

        // Assert
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'state' => 'Received',
        ]);
    }

    /** @test */
    public function it_can_get_purchase_statistics()
    {
        // Arrange
        Purchase::factory()->create(['state' => 'Paid', 'total_net' => 100, 'discount' => 10]);
        Purchase::factory()->create(['state' => 'Paid', 'total_net' => 150, 'discount' => 0]);
        Purchase::factory()->create(['state' => 'Ordered', 'total_net' => 200, 'discount' => 20]); // Not paid

        // Act
        $stats = $this->service->getPurchaseStatistics();

        // Assert
        $this->assertEquals(250.00, $stats['totalSpent']); // 100 + 150
        $this->assertEquals(2, $stats['totalPurchases']);
        $this->assertEquals(10, $stats['totalDiscounts']); // 10 + 0 + 20
        $this->assertEqualsWithDelta(125.00, $stats['averagePurchaseValue'], 0.01); // (100+150)/2
    }
}
