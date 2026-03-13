<?php

namespace Tests\Feature\service;

use App\Models\Product;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\StockService;
use App\Models\PurchaseItem;
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
        // Créer un utilisateur pour l'authentification si nécessaire (selon middleware)
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_update_a_draft_purchase()
    {
        // 1. Préparer les données
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create(); // Nouveau fournisseur

        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 200]);

        // Créer une commande en Draft
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier1->id,
            'state' => 'Draft',
            'total_amount' => 1000,
        ]);

        // Ligne existante
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        // 2. Données de mise à jour (Changement fournisseur + Changement produit + Ajout produit)
        $updateData = [
            'supplier_id' => $supplier2->id,
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 5, // Quantité modifiée (10 -> 5)
                    'unit_price' => 110, // Prix modifié
                ],
                [
                    'product_id' => $product2->id, // Nouveau produit
                    'quantity' => 2,
                    'unit_price' => 150,
                ]
            ]
        ];

        // 3. Action
        $response = $this->put(route('admin.purchases.update', $purchase->id), $updateData);

        // 4. Assertions
        $response->assertRedirect(route('admin.purchases.show', $purchase->id));
        $response->assertSessionHas('success');

        // Vérifier la mise à jour de l'entête
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'supplier_id' => $supplier2->id,
            'total_amount' => (5 * 110) + (2 * 150), // 550 + 300 = 850
        ]);

        // Vérifier les lignes (les anciennes doivent être supprimées/remplacées)
        $this->assertCount(2, $purchase->fresh()->items);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'unit_price' => 110,
        ]);
    }

    /** @test */
    public function it_cannot_update_a_received_purchase()
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'state' => 'Received', // Déjà reçu
        ]);

        $updateData = [
            'supplier_id' => $supplier->id,
            'products' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]]
        ];

        $response = $this->put(route('admin.purchases.update', $purchase->id), $updateData);

        $response->assertSessionHas('error'); // Doit retourner une erreur
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
