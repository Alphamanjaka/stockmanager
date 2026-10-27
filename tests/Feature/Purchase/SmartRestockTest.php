<?php

namespace Tests\Feature\Purchase;

use App\Core\Services\SettingService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réapprovisionnement selon la vitesse de vente : une variante est proposée quand son stock (plus ce qui est
 * commandé) ne tient plus jusqu'à la prochaine livraison en gardant le seuil d'alerte en réserve, et la quantité
 * couvre le délai du fournisseur plus les jours de ventes réglés.
 */
class SmartRestockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    private function variant(int $stock, int $alert = 5): ProductColor
    {
        $product = Product::factory()->create(['name' => 'Wax', 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'alert_stock' => $alert, 'price' => 10000, 'price_purchase' => 6000,
        ]);
    }

    /** Dernier achat reçu chez un fournisseur dont le délai est $leadTime jours. */
    private function supplier(ProductColor $variant, ?int $leadTime): Supplier
    {
        $supplier = Supplier::factory()->create(['lead_time_days' => $leadTime]);
        $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id, 'state' => Purchase::RECEIVED]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_color_id' => $variant->id, 'quantity' => 1,
            'received_quantity' => 1, 'unit_price' => 6000, 'subtotal' => 6000,
        ]);

        return $supplier;
    }

    private function sell(ProductColor $variant, int $quantity, int $daysAgo = 1): void
    {
        $this->travel(-$daysAgo)->days();
        app(StockService::class)->removeStock($variant->id, $quantity, 'Vente', MovementSource::sale(1));
        $this->travelBack();
    }

    private function suggestion(ProductColor $variant): ?object
    {
        return app(PurchaseService::class)->getShortageProductsGroupedBySupplier()
            ->flatMap(fn ($group) => $group['products'])
            ->firstWhere('id', $variant->id);
    }

    /** @test */
    public function a_fast_seller_is_suggested_before_reaching_its_threshold()
    {
        // 60 vendus en 30 jours : 2 par jour. Stock 20, seuil 5, livraison en 10 jours : il faut 5 + 20 = 25
        $variant = $this->variant(80);
        $this->supplier($variant, 10);
        $this->sell($variant, 60);

        $suggestion = $this->suggestion($variant);
        $this->assertSame(60, $suggestion->sold_quantity);
        $this->assertEquals(2, $suggestion->daily_sales);
        $this->assertSame(10, $suggestion->days_left);
        $this->assertSame(10, $suggestion->lead_time_days);
        // À la livraison : seuil (5) + 2 × (10 jours de délai + 30 jours à couvrir) = 85, moins le stock (20)
        $this->assertSame(65, $suggestion->suggested_quantity);

        $this->get(route('admin.purchases.createFromShortage'))
            ->assertOk()
            ->assertSee('Sales / day')
            ->assertSee('60 sold in 30 days')
            ->assertSee('Delivery in 10 d');
    }

    /** @test */
    public function a_shorter_lead_time_waits_longer_before_suggesting()
    {
        // Même vitesse, livraison en 5 jours : 5 + 10 = 15 suffisent, le stock (20) tient
        $variant = $this->variant(80);
        $this->supplier($variant, 5);
        $this->sell($variant, 60);

        $this->assertNull($this->suggestion($variant));
    }

    /** @test */
    public function the_default_lead_time_and_coverage_come_from_the_settings()
    {
        app(SettingService::class)->updateSettings(['default_lead_time_days' => '10', 'restock_coverage_days' => '15']);

        // Sans fournisseur, ou fournisseur sans délai : délai par défaut (10 jours)
        $variant = $this->variant(80);
        $this->supplier($variant, null);
        $this->sell($variant, 60);

        $suggestion = $this->suggestion($variant);
        $this->assertSame(10, $suggestion->lead_time_days);
        // 5 + 2 × (10 + 15) = 55, moins 20
        $this->assertSame(35, $suggestion->suggested_quantity);
    }

    /** @test */
    public function returns_are_deducted_and_old_sales_count_only_over_90_days()
    {
        // Seuil 30 : proposé quelle que soit la vitesse (stock final 20)
        $variant = $this->variant(95, alert: 30);
        $this->sell($variant, 40);
        app(StockService::class)->addStock($variant->id, 10, 'Retour', null, MovementSource::saleReturn(1));
        $this->sell($variant, 45, daysAgo: 60);

        // 30 jours : 40 vendus, 10 rendus
        $this->assertSame(30, $this->suggestion($variant)->sold_quantity);

        app(SettingService::class)->updateSettings(['restock_sales_period' => '90']);
        $this->assertSame(75, $this->suggestion($variant)->sold_quantity);
        $this->assertSame(90, $this->suggestion($variant)->sales_period);
    }

    /** @test */
    public function a_variant_without_threshold_is_followed_once_it_sells()
    {
        // Seuil 0 et aucune vente : pas surveillée, même à 0
        $this->assertNull($this->suggestion($this->variant(0, alert: 0)));

        // Seuil 0 mais 1 vente par jour : stock 3 pour 7 jours de délai
        $selling = $this->variant(33, alert: 0);
        $this->sell($selling, 30);

        $suggestion = $this->suggestion($selling);
        $this->assertSame(3, $suggestion->days_left);
        // 0 + 1 × (7 + 30) = 37, moins 3
        $this->assertSame(34, $suggestion->suggested_quantity);
    }

    /** @test */
    public function without_sales_the_threshold_rule_is_unchanged()
    {
        // Stock 1, seuil 5 : remonter à 7,5
        $suggestion = $this->suggestion($this->variant(1));

        $this->assertSame(0, $suggestion->sold_quantity);
        $this->assertNull($suggestion->days_left);
        $this->assertSame(7, $suggestion->suggested_quantity);
    }

    /** @test */
    public function the_supplier_lead_time_is_saved_from_its_form()
    {
        $this->post(route('admin.suppliers.store'), ['name' => 'Tissus Mada', 'phone' => '034 00 000 00', 'lead_time_days' => 12])
            ->assertRedirect();
        $supplier = Supplier::firstWhere('name', 'tissus mada');
        $this->assertSame(12, $supplier->lead_time_days);

        $this->put(route('admin.suppliers.update', $supplier), ['name' => 'Tissus Mada', 'lead_time_days' => ''])->assertRedirect();
        $this->assertNull($supplier->fresh()->lead_time_days);

        $this->post(route('admin.suppliers.store'), ['name' => 'Autre', 'phone' => '1', 'lead_time_days' => -1])
            ->assertSessionHasErrors('lead_time_days');
    }
}
