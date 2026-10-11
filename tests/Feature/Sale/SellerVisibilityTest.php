<?php

namespace Tests\Feature\Sale;

use App\Models\User;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un vendeur ne voit que ses propres ventes ; le back office voit tout.
 */
class SellerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;
    private User $bob;
    private Sale $aliceSale;
    private Sale $bobSale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::factory()->frontOffice()->create();
        $this->bob = User::factory()->frontOffice()->create();
        $this->aliceSale = Sale::factory()->create(['reference' => 'SALE-ALICE', 'user_id' => $this->alice->id, 'total_net' => 300, 'created_at' => now()]);
        $this->bobSale = Sale::factory()->create(['reference' => 'SALE-BOB', 'user_id' => $this->bob->id, 'total_net' => 5000, 'created_at' => now()]);
    }

    /** @test */
    public function a_seller_only_lists_their_own_sales_and_statistics()
    {
        $this->actingAs($this->alice)
            ->get(route('saler.index'))
            ->assertOk()
            ->assertSee('SALE-ALICE')
            ->assertDontSee('SALE-BOB')
            ->assertViewHas('total_revenue', fn ($total) => (float) $total === 300.0)
            ->assertViewHas('today_sales_count', 1);
    }

    /** @test */
    public function a_seller_cannot_open_or_download_another_sellers_sale()
    {
        $this->actingAs($this->alice);

        $this->get(route('saler.show', $this->aliceSale->id))->assertOk();
        $this->get(route('saler.show', $this->bobSale->id))->assertNotFound();
        $this->get(route('saler.pdf', $this->bobSale->id))->assertNotFound();
    }

    /** @test */
    public function the_seller_dashboard_only_counts_their_own_sales()
    {
        $this->actingAs($this->alice)
            ->get(route('saler.dashboard'))
            ->assertOk()
            ->assertViewHas('totalSales', 1)
            ->assertViewHas('salesToday', fn ($total) => (float) $total === 300.0);
    }

    /** @test */
    public function the_back_office_sees_every_sale()
    {
        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('SALE-ALICE')
            ->assertSee('SALE-BOB');
    }
}
