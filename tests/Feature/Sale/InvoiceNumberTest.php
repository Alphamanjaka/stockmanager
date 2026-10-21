<?php

namespace Tests\Feature\Sale;

use App\Core\Services\DocumentNumberService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Factures numérotées à la suite, avec un compteur par année : FAC-2026-000001, FAC-2026-000002...
 */
class InvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    private ProductColor $wax;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-23 10:00'));
        $this->seller = User::factory()->frontOffice()->create();

        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $this->wax = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 10, 'price' => 1000,
        ]);
    }

    private function sell(int $quantity = 1): string
    {
        return app(SaleService::class)
            ->createSale([['product_color_id' => $this->wax->id, 'quantity' => $quantity]], 0, $this->seller->id)
            ->reference;
    }

    /** @test */
    public function invoices_follow_each_other()
    {
        $this->assertSame('FAC-2026-000001', $this->sell());
        $this->assertSame('FAC-2026-000002', $this->sell());
        $this->assertSame('FAC-2026-000003', $this->sell());
    }

    /** @test */
    public function the_counter_restarts_each_year()
    {
        $this->assertSame('FAC-2026-000001', $this->sell());
        $this->assertSame('FAC-2026-000002', $this->sell());

        $this->travelTo(Carbon::parse('2027-01-01 08:00'));
        $this->assertSame('FAC-2027-000001', $this->sell());
    }

    /** @test */
    public function a_refused_sale_does_not_use_a_number()
    {
        $this->assertSame('FAC-2026-000001', $this->sell());

        try {
            $this->sell(50); // stock insuffisant : la vente est annulée
            $this->fail('La vente aurait dû être refusée');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
        }

        $this->assertSame('FAC-2026-000002', $this->sell(), 'Pas de trou dans la suite');
    }

    /** @test */
    public function each_prefix_has_its_own_counter()
    {
        $numbers = app(DocumentNumberService::class);

        $this->assertSame('FAC-2026-000001', $numbers->next('FAC'));
        $this->assertSame('AVO-2026-000001', $numbers->next('AVO'));
        $this->assertSame('FAC-2026-000002', $numbers->next('FAC'));
        $this->assertSame('FAC-2025-000001', $numbers->next('FAC', Carbon::parse('2025-12-31')));
    }
}
