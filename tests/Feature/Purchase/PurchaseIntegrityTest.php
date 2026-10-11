<?php

namespace Tests\Feature\Purchase;

use App\Core\Services\GlobalSearchService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Contact\Services\SupplierService;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function variant(string $productName, int $stock): ProductColor
    {
        $product = Product::factory()->create(['name' => $productName, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::create(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => $stock, 'price' => 1000]);
    }

    private function receivedPurchase(?int $supplierId, array $lines): Purchase
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $supplierId, 'state' => 'Received', 'reference' => 'PUR-TEST']);
        foreach ($lines as [$variant, $quantity]) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id, 'product_color_id' => $variant->id,
                'quantity' => $quantity, 'unit_price' => 100, 'subtotal' => 100 * $quantity,
            ]);
        }

        return $purchase;
    }

    /** @test */
    public function deleting_a_received_purchase_is_all_or_nothing()
    {
        $kept = $this->variant('Wax', 10);
        $sold = $this->variant('Velours', 1); // 5 reçus mais 4 déjà vendus : impossible de tout retirer
        $purchase = $this->receivedPurchase(null, [[$kept, 5], [$sold, 5]]);

        $thrown = null;
        try {
            app(PurchaseService::class)->deletePurchase($purchase->id);
        } catch (\Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertModelExists($purchase);
        $this->assertSame(10, $kept->fresh()->stock, 'Le retrait de la première ligne doit être annulé');
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** @test */
    public function global_search_handles_purchases_without_supplier()
    {
        $this->receivedPurchase(null, []);

        $results = app(GlobalSearchService::class)->search('PUR-TEST');

        $this->assertSame('Achat #PUR-TEST (Sans fournisseur)', $results->firstWhere('type', 'purchase')['name']);
    }

    /** @test */
    public function purchases_api_ignores_unknown_sort_fields()
    {
        $this->receivedPurchase(null, []);

        $this->getJson(route('admin.purchases.get-purchases-api', [
            'sort' => [['field' => 'supplier.name', 'dir' => 'asc'], ['field' => 'reference', 'dir' => 'desc; drop']],
            'size' => 100000,
        ]))->assertOk()->assertJsonPath('data.0.reference', 'PUR-TEST');
    }

    /** @test */
    public function supplier_top_products_are_grouped_by_product()
    {
        $supplier = Supplier::factory()->create();
        $wax = $this->variant('Wax', 0);
        $this->receivedPurchase($supplier->id, [[$wax, 4]]);

        $details = app(SupplierService::class)->getSupplierDetails($supplier);

        $this->assertSame('Wax', $details['topProducts']->first()->name);
        $this->assertEquals(4, $details['topProducts']->first()->total_qty);
    }
}
