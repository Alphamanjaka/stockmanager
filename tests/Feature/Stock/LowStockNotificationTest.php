<?php

namespace Tests\Feature\Stock;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Notifications\LowStockAlert;
use App\Modules\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cloche du back office : une variante qui passe sous son seuil d'alerte notifie les administrateurs.
 */
class LowStockNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create();
        $this->stock = app(StockService::class);
    }

    private function variant(int $stock, int $alert = 5, string $name = 'Wax'): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'alert_stock' => $alert, 'price' => 1000,
        ]);
    }

    /** @test */
    public function crossing_the_alert_threshold_notifies_the_active_administrators()
    {
        $seller = User::factory()->frontOffice()->create();
        $archived = User::factory()->backOffice()->create(['archived_at' => now()]);
        $variant = $this->variant(6);

        $this->stock->removeStock($variant->id, 1, 'Vente SALE-1');

        $this->assertSame(1, $this->admin->unreadNotifications()->count());
        $this->assertSame(0, $seller->notifications()->count());
        $this->assertSame(0, $archived->notifications()->count());

        $notification = $this->admin->unreadNotifications()->first();
        $this->assertSame(LowStockAlert::class, $notification->type);
        $this->assertSame($variant->id, $notification->data['product_color_id']);
        $this->assertSame(5, $notification->data['stock']);
        $this->assertSame($variant->fresh()->label, $notification->data['label']);
        $this->assertSame(route('admin.products.show', $variant->product_id, false), $notification->data['url']);
    }

    /** @test */
    public function only_the_crossing_is_notified()
    {
        $variant = $this->variant(10);

        $this->stock->removeStock($variant->id, 2, 'Vente');          // 8 : au-dessus du seuil
        $this->stock->correct($variant->id, 'out', 3, 'casse');        // 5 : passage sous le seuil
        $this->stock->removeStock($variant->id, 2, 'Vente');          // 3 : déjà en dessous
        $this->stock->adjustTo($variant->id, 1, 'Inventaire INV-1');   // 1 : toujours en dessous

        $this->assertSame(1, $this->admin->notifications()->count());
    }

    /** @test */
    public function no_alert_without_threshold_or_for_an_archived_product()
    {
        $noThreshold = $this->variant(3, 0);
        $archived = $this->variant(6, 5, 'Ancien modèle');
        $archived->product->archive();

        $this->stock->removeStock($noThreshold->id, 3, 'Vente');
        $this->stock->removeStock($archived->id, 2, 'Vente');

        $this->assertSame(0, $this->admin->notifications()->count());
    }

    /** @test */
    public function restocking_above_the_threshold_clears_the_alert()
    {
        $variant = $this->variant(6);
        $other = $this->variant(6, 5, 'Velours');
        $this->stock->removeStock($variant->id, 2, 'Vente');
        $this->stock->removeStock($other->id, 2, 'Vente');

        $this->stock->addStock($variant->id, 10, 'Réception Achat #PUR-1');

        $this->assertSame(1, $this->admin->unreadNotifications()->count());
        $this->assertSame($other->id, $this->admin->unreadNotifications()->first()->data['product_color_id']);
    }

    /** @test */
    public function a_cancelled_operation_leaves_no_notification()
    {
        $variant = $this->variant(6);

        try {
            DB::transaction(function () use ($variant) {
                $this->stock->removeStock($variant->id, 2, 'Vente');
                throw new \RuntimeException('ligne suivante en rupture');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(6, $variant->fresh()->stock);
        $this->assertSame(0, $this->admin->notifications()->count());
    }

    /** @test */
    public function the_bell_shows_the_alert_and_opening_it_marks_it_read()
    {
        $variant = $this->variant(6, 5, 'Savon repardine');
        $this->stock->removeStock($variant->id, 2, 'Vente');
        $notification = $this->admin->unreadNotifications()->first();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Savon repardine')
            ->assertSee(route('admin.notifications.read', $notification->id), false)
            ->assertSee('Mark all as read');

        $this->get(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route('admin.products.show', $variant->product_id));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('No notifications found.');
    }

    /** @test */
    public function all_alerts_can_be_marked_as_read()
    {
        $this->stock->removeStock($this->variant(6)->id, 2, 'Vente');
        $this->stock->removeStock($this->variant(6, 5, 'Velours')->id, 2, 'Vente');

        $this->actingAs($this->admin)->from(route('admin.dashboard'))
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    /** @test */
    public function a_user_cannot_open_the_notification_of_another_user()
    {
        $this->stock->removeStock($this->variant(6)->id, 2, 'Vente');
        $notification = $this->admin->notifications()->first();

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.notifications.read', $notification->id))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
