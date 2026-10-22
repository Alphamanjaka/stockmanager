<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\SaleReturnService;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TVA : une entreprise assujettie (réglage « vat_enabled ») voit la TVA sur ses factures, reçus et avoirs.
 * Les prix de vente sont TTC ; le taux est figé sur chaque vente ; la marge est calculée hors taxes.
 * Sans ce réglage, aucune TVA n'apparaît, même si un taux est enregistré.
 */
class InvoiceVatTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private ProductColor $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
        $this->seller = User::factory()->frontOffice()->create();

        $product = Product::factory()->create(['name' => 'perruque lisse', 'category_id' => Category::factory()->create()->id]);
        $this->variant = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 10, 'price' => 12000, 'price_purchase' => 8000,
        ]);
    }

    private function sale(int $quantity = 1): Sale
    {
        return app(SaleService::class)->createSale([['product_color_id' => $this->variant->id, 'quantity' => $quantity]], 0, $this->seller->id);
    }

    private function registerForVat(float $rate): void
    {
        settings()->updateSettings(['vat_enabled' => '1', 'default_tax_rate' => $rate]);
    }

    /** @test */
    public function without_vat_registration_no_vat_is_shown_even_with_a_rate()
    {
        settings()->updateSettings(['default_tax_rate' => 20]); // le taux seul ne suffit pas
        $sale = $this->sale();

        $this->assertNull($sale->vat_rate);
        $this->assertSame(12000.0, $sale->excludingVat(12000));

        $this->get(route('admin.sales.show', $sale->id))->assertOk()->assertDontSee('Total excl. VAT')->assertSee('Net total');
        $this->get(route('admin.sales.receipt', $sale->id))->assertOk()->assertDontSee(__('pos.total_excl_vat'));
        $this->assertStringNotContainsString(__('documents.total_excl_vat'), view('sale::sales.pdf', ['sale' => $sale->fresh()])->render());
    }

    /** @test */
    public function a_registered_business_shows_the_vat_included_in_its_prices()
    {
        $this->registerForVat(20);
        $sale = $this->sale();

        $this->assertEquals(20, $sale->vat_rate);
        $this->assertSame(10000.0, $sale->excludingVat(12000));
        $this->assertSame(2000.0, $sale->vatIncluded(12000));

        $this->get(route('admin.sales.show', $sale->id))->assertOk()
            ->assertSee('Unit price incl. VAT')
            ->assertSeeInOrder(['Total excl. VAT', '10,000.00', 'VAT 20 %', '2,000.00', 'Total incl. VAT', '12,000.00']);
        $this->get(route('admin.sales.receipt', $sale->id))->assertOk()
            ->assertSeeInOrder([__('pos.total_excl_vat'), '10,000', __('pos.vat', ['rate' => 20]), '2,000', __('pos.total_incl_vat')]);

        $pdf = view('sale::sales.pdf', ['sale' => $sale->fresh()])->render();
        $this->assertStringContainsString(__('documents.total_excl_vat'), $pdf);
        $this->assertStringContainsString(__('documents.vat', ['rate' => 20]), $pdf);
        $this->assertStringContainsString(__('documents.total_due_incl_vat'), $pdf);
    }

    /** @test */
    public function each_sale_keeps_the_rate_of_its_day()
    {
        $this->registerForVat(20);
        $before = $this->sale();

        $this->registerForVat(10);
        $after = $this->sale();
        settings()->updateSettings(['vat_enabled' => '0']);
        $unregistered = $this->sale();

        $this->assertEquals(20, $before->fresh()->vat_rate);
        $this->assertEquals(10, $after->fresh()->vat_rate);
        $this->assertNull($unregistered->fresh()->vat_rate);
        $this->get(route('admin.sales.show', $before->id))->assertSee('VAT 20 %')->assertSee('2,000.00');
    }

    /** @test */
    public function the_margin_is_calculated_excluding_vat_returns_included()
    {
        $this->registerForVat(20);
        $sale = $this->sale(2); // 24 000 TTC, soit 20 000 HT, pour 16 000 de coût

        $this->assertSame(4000.0, $sale->fresh()->grossMargin());
        $this->assertSame(20.0, $sale->fresh()->marginRate()); // 4 000 / 20 000

        // Un article rendu : 12 000 TTC remboursés, 10 000 HT et 8 000 de coût en moins
        $return = app(SaleReturnService::class)->create($sale->id, [$sale->items()->value('id') => 1], 'Mauvaise taille');

        $this->assertSame(2000.0, $sale->fresh()->grossMargin());
        $margin = app(SaleService::class)->getGrossMargin(now()->startOfDay(), now()->endOfDay());
        $this->assertSame(10000.0, $margin['revenue']);
        $this->assertSame(2000.0, $margin['margin']);
        $this->assertSame(20.0, $margin['rate']);

        // L'avoir reprend la TVA de la vente
        $this->get(route('admin.sale-returns.show', $return->id))->assertOk()
            ->assertSeeInOrder(['Total excl. VAT', '10,000.00', 'VAT 20 %', '2,000.00', 'Total refunded', '12,000.00']);
        $this->get(route('admin.sales.show', $sale->id))->assertSee('Calculated excluding VAT');
    }

    /** @test */
    public function the_settings_switch_registers_the_business_for_vat_and_back()
    {
        $this->put('/admin/settings/1', ['vat_enabled' => '1', 'default_tax_rate' => 18])->assertSessionHasNoErrors();
        $this->assertEquals(18, $this->sale()->vat_rate);
        $this->assertMatchesRegularExpression('/id="vat_enabled"[^>]*\schecked/', $this->get(route('admin.settings.index'))->getContent());

        // Case décochée : seul le champ caché est envoyé
        $this->put('/admin/settings/1', ['vat_enabled' => '0'])->assertSessionHasNoErrors();
        $this->assertNull($this->sale()->vat_rate);
    }
}
