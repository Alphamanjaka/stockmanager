<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Facture et bon de commande : gabarit PDF commun (layouts.pdf), traduits, et la facture garde
 * les coordonnées et la devise enregistrées avec la vente.
 */
class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private ProductColor $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->frontOffice()->create(['name' => 'Hery']);
        $this->actingAs(User::factory()->backOffice()->create());

        $product = Product::factory()->create(['name' => 'perruque lisse', 'category_id' => Category::factory()->create()->id]);
        $this->variant = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 5, 'price' => 1000,
        ]);

        settings()->updateSettings(['company_name' => 'Nouveau nom', 'currency_symbol' => 'EUR', 'company_vat' => 'TVA-42']);
    }

    private function sale(array $attributes = []): Sale
    {
        $sale = Sale::factory()->create($attributes + [
            'user_id' => $this->seller->id,
            'customer_id' => Customer::factory()->create(['name' => 'Rasoa'])->id,
            'company_name' => 'Ancien nom',
            'currency_symbol' => 'MGA',
            'total_brut' => 2000, 'discount' => 500, 'total_net' => 1500,
            'payment_method' => 'cash', 'amount_received' => 2000,
        ]);
        SaleItem::create(['sale_id' => $sale->id, 'product_color_id' => $this->variant->id, 'quantity' => 2, 'unit_price' => 1000, 'subtotal' => 2000]);

        return $sale->load('items.productColor.product', 'customer', 'user');
    }

    /** @test */
    public function the_invoice_keeps_the_company_and_currency_of_the_sale()
    {
        $html = view('sale::sales.pdf', ['sale' => $this->sale()])->render();

        $this->assertStringContainsString('Ancien nom', $html);
        $this->assertStringNotContainsString('Nouveau nom', $html);
        $this->assertStringContainsString('1,500.00 MGA', $html);
        $this->assertStringNotContainsString('EUR', $html);
        // Client, vendeur, remise, paiement en espèces avec monnaie rendue
        $this->assertStringContainsString('Rasoa', $html);
        $this->assertStringContainsString('Hery', $html);
        $this->assertStringContainsString('-500.00 MGA', $html);
        $this->assertStringContainsString('500.00 MGA', $html);
        // Identifiant légal des Paramètres dans le pied
        $this->assertStringContainsString('TVA-42', $html);
    }

    /** @test */
    public function an_older_sale_without_saved_details_uses_the_settings()
    {
        $html = view('sale::sales.pdf', ['sale' => $this->sale(['company_name' => null])])->render();

        $this->assertStringContainsString('Nouveau nom', $html);
    }

    /** @test */
    public function documents_follow_the_application_language()
    {
        app()->setLocale('fr');
        $html = view('sale::sales.pdf', ['sale' => $this->sale()])->render();

        $this->assertStringContainsString('Facture', $html);
        $this->assertStringContainsString('Net à payer', $html);
        $this->assertStringContainsString('Espèces', $html);
    }

    /** @test */
    public function the_purchase_order_shows_its_supplier_status_and_notes()
    {
        app()->setLocale('fr');
        $purchase = Purchase::factory()->create([
            'supplier_id' => Supplier::factory()->create(['name' => 'Tissus Mada'])->id,
            'state' => 'Ordered',
            'supplier_reference' => 'BL-778',
            'notes' => 'Livraison avant vendredi',
            'total_amount' => 1400, 'discount' => 0, 'total_net' => 1400,
        ]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_color_id' => $this->variant->id, 'quantity' => 2, 'unit_price' => 700, 'subtotal' => 1400]);

        $html = view('purchase::purchases.pdf', ['purchase' => $purchase->load('items.productColor.product', 'supplier')])->render();

        foreach (['Bon de commande', 'Tissus Mada', 'Commandé', 'BL-778', 'Livraison avant vendredi', '1,400.00 EUR'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    /** @test */
    public function document_labels_exist_in_every_language()
    {
        $keys = fn (string $locale) => array_keys(\Illuminate\Support\Arr::dot(require lang_path("{$locale}/documents.php")));

        foreach (array_keys(config('app.supported_locales')) as $locale) {
            $this->assertSame($keys('en'), $keys($locale), "lang/{$locale}/documents.php");
        }
    }

    /** @test */
    public function invoices_and_purchase_orders_download_as_pdf()
    {
        $sale = $this->sale();
        $purchase = Purchase::factory()->create(['supplier_id' => null]);

        $this->get(route('admin.sales.pdf', $sale->id))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('admin.purchases.pdf', $purchase->id))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('admin.purchases.pdf.preview', $purchase->id))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->seller)
            ->get(route('saler.pdf', $sale->id))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
