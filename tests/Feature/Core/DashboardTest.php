<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tableau de bord du back office, orienté action : ventes du jour et marge, commandes à recevoir,
 * ruptures à réapprovisionner, stock dormant ; chiffre d'affaires de la semaine en cours (du lundi au dimanche).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private ?Category $category = null;

    private function variant(int $stock, int $alert = 0, ?float $cost = null, bool $archived = false): ProductColor
    {
        // Une seule catégorie : la factory n'a que quatre noms différents
        $this->category ??= Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        if ($archived) {
            $product->archive();
        }

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'alert_stock' => $alert, 'price' => 5000, 'price_purchase' => $cost,
        ]);
    }

    private function movedOn(ProductColor $variant, string $date): void
    {
        StockMovement::factory()->create(['product_color_id' => $variant->id, 'created_at' => $date]);
    }

    /** @test */
    public function the_dashboard_shows_what_needs_action()
    {
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        $this->actingAs(User::factory()->backOffice()->create());

        // Ventes du jour : celle d'hier n'y est pas
        Sale::factory()->create(['total_net' => 300, 'created_at' => '2026-10-15 09:00']);
        Sale::factory()->create(['total_net' => 500, 'created_at' => '2026-10-14 18:00']);

        // Commandes à recevoir : 4 000 attendus sur la première, 2 000 sur la seconde (6 livrés sur 10)
        $service = app(PurchaseService::class);
        $ordered = $service->changeState($service->processPurchase(null, [
            ['product_color_id' => $this->variant(0)->id, 'quantity' => 4, 'unit_price' => 1000],
        ]), Purchase::ORDERED);
        $partial = $service->changeState($service->processPurchase(null, [
            ['product_color_id' => $this->variant(0)->id, 'quantity' => 10, 'unit_price' => 500],
        ]), Purchase::ORDERED);
        $service->receive($partial, [$partial->items()->value('id') => 6]);

        // Rupture : stock 1 pour un seuil de 5, sans commande en cours
        $shortage = $this->variant(1, 5);
        $this->movedOn($shortage, '2026-10-10');

        // Stock dormant : aucun mouvement depuis 60 jours (ou jamais) ; un produit archivé n'est pas compté
        $this->movedOn($this->variant(4, 0, 1000), '2026-07-01');
        $this->variant(2, 0, 500);
        $this->movedOn($this->variant(3, 0, 1000), '2026-10-01');
        $this->variant(9, 0, 1000, archived: true);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('salesToday', fn ($total) => (float) $total === 300.0)
            ->assertViewHas('salesTodayCount', 1)
            ->assertViewHas('awaitingDelivery', fn ($orders) => $orders['count'] === 2 && $orders['value'] === 6000.0
                && $orders['purchases']->first()->is($ordered))
            ->assertViewHas('shortages', fn ($shortages) => $shortages['count'] === 1 && $shortages['below_threshold'] === 1
                && $shortages['items']->first()->id === $shortage->id)
            ->assertViewHas('dormantStock', fn ($dormant) => $dormant['count'] === 2 && $dormant['value'] === 5000.0)
            ->assertSee('Orders to receive')
            ->assertSee($ordered->reference)
            ->assertSee('To restock')
            ->assertSee('Dormant stock')
            ->assertSee('Never moved')
            ->assertViewMissing('totalSuppliers');
    }

    /** @test */
    public function the_back_office_dashboard_shows_the_sales_of_the_current_week()
    {
        // Jeudi 15 octobre 2026 : la semaine va du lundi 12 au dimanche 18
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));

        Sale::factory()->create(['total_net' => 100, 'created_at' => '2026-10-12 08:00']); // lundi
        Sale::factory()->create(['total_net' => 250, 'created_at' => '2026-10-15 09:30']); // aujourd'hui
        Sale::factory()->create(['total_net' => 999, 'created_at' => '2026-10-11 23:59']); // dimanche précédent

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("This week's sales")
            ->assertDontSee("Today's sales")
            ->assertViewHas('salesThisWeek', fn ($total) => (float) $total === 350.0);
    }
}
