<?php

namespace Tests\Feature\Core;

use App\Core\Mail\DailySummaryMail;
use App\Core\Services\DailySummaryService;
use App\Core\Services\SettingService;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Services\SaleReturnService;
use App\Modules\Sale\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Résumé quotidien : chiffres de la journée, aperçu dans le back office, e-mail d'essai et envoi planifié.
 */
class DailySummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    private ProductColor $wax;

    private ProductColor $savon;

    protected function setUp(): void
    {
        parent::setUp();
        // Mardi 27 octobre 2026, 20 h
        $this->travelTo(Carbon::parse('2026-10-27 20:00'));
        // Fuseau de la boutique : celui de l'application (UTC), pour lire les heures telles quelles
        $this->settings(['timezone' => 'UTC']);
        $this->admin = User::factory()->backOffice()->create(['email' => 'gerant@boutique.mg']);
        $this->seller = User::factory()->frontOffice()->create(['name' => 'Hery']);

        $category = Category::factory()->create();
        $this->wax = $this->variant('Wax', 10000, 6000, $category);
        $this->savon = $this->variant('Savon', 2000, 1000, $category);
    }

    private function variant(string $name, float $price, float $cost, Category $category): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => $category->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => 50, 'price' => $price, 'price_purchase' => $cost, 'alert_stock' => 5,
        ]);
    }

    private function sell(ProductColor $variant, int $quantity, array $payment = [])
    {
        return app(SaleService::class)->createSale([['product_color_id' => $variant->id, 'quantity' => $quantity]], 0, $this->seller->id, $payment);
    }

    private function settings(array $values): void
    {
        app(SettingService::class)->updateSettings($values);
    }

    /** @test */
    public function the_summary_gives_the_figures_of_the_day()
    {
        // Mardi précédent : 10 000
        $this->travelTo(Carbon::parse('2026-10-20 11:00'));
        $this->sell($this->wax, 1);
        $this->travelTo(Carbon::parse('2026-10-27 10:00'));

        $this->sell($this->wax, 2);                                                       // 20 000 en espèces
        $sale = $this->sell($this->savon, 5, ['payment_method' => 'mobile_money', 'payment_operator' => 'mvola', 'payment_reference' => 'MP1']); // 10 000
        app(SaleReturnService::class)->create($sale->id, [$sale->items()->value('id') => 1], 'Abîmé', userId: $this->admin->id); // 2 000 rendus
        $this->travelTo(Carbon::parse('2026-10-27 20:00'));

        $summary = app(DailySummaryService::class)->build(now());

        $this->assertEquals(28000, $summary['revenue']);
        $this->assertEquals(2000, $summary['refunds']);
        $this->assertSame(2, $summary['sales_count']);
        $this->assertEquals(15000, $summary['average']);
        $this->assertEquals(180.0, $summary['previous']['change'], '28 000 contre 10 000 le mardi précédent');
        // Marge : (20 000 − 12 000) + (8 000 − 4 000) = 12 000
        $this->assertEquals(12000, $summary['margin']['margin']);
        $this->assertSame([
            ['method' => 'cash', 'operator' => null, 'count' => 1, 'total' => 20000.0],
            ['method' => 'mobile_money', 'operator' => 'mvola', 'count' => 1, 'total' => 10000.0],
        ], $summary['payments']);
        $this->assertStringStartsWith('Wax', $summary['top_products'][0]['label']);
        $this->assertSame([['name' => 'Hery', 'count' => 2, 'total' => 30000.0]], $summary['sellers']);
    }

    /** @test */
    public function the_page_previews_any_past_day_without_sending()
    {
        Mail::fake();
        $this->sell($this->wax, 3);

        $this->actingAs($this->admin)->get(route('admin.daily-summary.index'))
            ->assertOk()
            ->assertSee(route('admin.daily-summary.preview', ['date' => '2026-10-27']), false);

        $this->actingAs($this->admin)->get(route('admin.daily-summary.preview', ['date' => '2026-10-27']))
            ->assertOk()
            ->assertSee('30,000');

        // La veille : aucune vente ; un jour futur retombe sur aujourd'hui
        $this->actingAs($this->admin)->get(route('admin.daily-summary.preview', ['date' => '2026-10-26']))->assertOk()->assertDontSee('30,000');
        $this->actingAs($this->admin)->get(route('admin.daily-summary.preview', ['date' => '2030-01-01']))->assertOk()->assertSee('30,000');

        Mail::assertNothingSent();
    }

    /** @test */
    public function the_settings_are_saved_and_wrong_addresses_refused()
    {
        $this->actingAs($this->admin)->put(route('admin.daily-summary.settings'), [
            'enabled' => '1', 'hour' => 19, 'recipients' => "patron@boutique.mg;\n compta@boutique.mg",
        ])->assertSessionHas('success');

        $summary = app(DailySummaryService::class);
        $this->assertTrue($summary->isEnabled());
        $this->assertSame(19, $summary->hour());
        $this->assertSame(['patron@boutique.mg', 'compta@boutique.mg'], $summary->recipients());

        $this->actingAs($this->admin)->put(route('admin.daily-summary.settings'), ['hour' => 19, 'recipients' => 'patron@boutique'])
            ->assertSessionHasErrors('recipients');
    }

    /** @test */
    public function the_test_e_mail_goes_to_the_current_user_only()
    {
        Mail::fake();
        $this->settings([DailySummaryService::RECIPIENTS => 'patron@boutique.mg']);

        $this->actingAs($this->admin)->post(route('admin.daily-summary.test'), ['date' => '2026-10-27'])
            ->assertSessionHas('success');

        Mail::assertSent(DailySummaryMail::class, fn ($mail) => $mail->hasTo('gerant@boutique.mg') && ! $mail->hasTo('patron@boutique.mg'));
    }

    /** @test */
    public function a_mail_server_error_is_shown_to_the_user()
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));

        $this->actingAs($this->admin)->post(route('admin.daily-summary.test'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Connection refused'));
    }

    /** @test */
    public function the_scheduled_sending_waits_for_its_hour_and_sends_once_a_day()
    {
        Mail::fake();

        // Désactivé : rien
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertNothingSent();

        $this->settings([DailySummaryService::ENABLED => '1', DailySummaryService::HOUR => '20']);

        // Pas encore l'heure
        $this->travelTo(Carbon::parse('2026-10-27 19:00'));
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertNothingSent();

        // 20 h : envoyé aux administrateurs (aucune adresse réglée), une seule fois
        $this->travelTo(Carbon::parse('2026-10-27 20:00'));
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertSent(DailySummaryMail::class, 1);
        Mail::assertSent(DailySummaryMail::class, fn ($mail) => $mail->hasTo('gerant@boutique.mg') && ! $mail->hasTo($this->seller->email));

        // Le lendemain à 20 h : de nouveau
        $this->travelTo(Carbon::parse('2026-10-28 20:00'));
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertSent(DailySummaryMail::class, 2);
    }

    /** @test */
    public function days_and_sending_time_follow_the_time_zone_of_the_shop()
    {
        Mail::fake();
        $this->settings(['timezone' => 'Indian/Antananarivo', DailySummaryService::ENABLED => '1', DailySummaryService::HOUR => '20']);

        // L'application est en UTC : 22 h UTC le 26, c'est 1 h du matin le 27 à Madagascar
        $this->travelTo(Carbon::parse('2026-10-26 22:00'));
        $this->sell($this->wax, 1);
        $this->travelTo(Carbon::parse('2026-10-27 12:00'));
        $this->sell($this->savon, 1);

        $summary = app(DailySummaryService::class);
        $this->assertEquals(12000, $summary->build(Carbon::parse('2026-10-27'))['revenue']);
        $this->assertEquals(0, $summary->build(Carbon::parse('2026-10-26'))['revenue']);

        // 20 h UTC, c'est 23 h à Madagascar : trop tard. 17 h UTC, c'est 20 h : envoyé
        $this->travelTo(Carbon::parse('2026-10-26 20:00'));
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-10-27 17:00'));
        $this->artisan('reports:daily-summary --scheduled')->assertSuccessful();
        Mail::assertSent(DailySummaryMail::class, fn ($mail) => $mail->summary['date']->toDateString() === '2026-10-27');
    }

    /** @test */
    public function sellers_cannot_see_the_summary()
    {
        $this->actingAs($this->seller)->get(route('admin.daily-summary.index'))->assertRedirect(route('dashboard'));
    }
}
