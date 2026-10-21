<?php

namespace Tests\Feature\Stock;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Mail\StockDropAlert;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Alerte sur les pertes non expliquées (corrections en sortie, écarts d'inventaire négatifs),
 * et plus sur la baisse de la valeur du stock : les ventes ne déclenchent rien.
 */
class MonitorStockDropTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StockService $stock;
    private ProductColor $wax;
    private ProductColor $savon;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::factory()->backOffice()->create(['email' => 'admin@example.com']);
        $this->stock = app(StockService::class);
        // Wax : 100 en stock, coût 6 000. Savon : 50 en stock, sans coût (valorisé au prix de vente, 2 000).
        // Valeur du stock : 600 000 + 100 000 = 700 000
        $this->wax = $this->variant('Wax', 100, 10000, 6000);
        $this->savon = $this->variant('Savon', 50, 2000, null);
    }

    private function variant(string $name, int $stock, float $price, ?float $cost): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id,
            'stock' => $stock, 'price' => $price, 'price_purchase' => $cost,
        ]);
    }

    /** @test */
    public function sales_and_cancelled_receipts_never_trigger_an_alert()
    {
        // 60 % du stock de wax vendu : ce n'est pas une perte
        $this->stock->removeStock($this->wax->id, 60, 'Vente SALE-ABC');
        $this->stock->removeStock($this->savon->id, 10, 'Annulation Réception Achat #PUR-1');

        $this->artisan('stock:monitor-drop', ['--threshold' => 1])
            ->expectsOutput('✅ Aucune perte non expliquée sur les 24 dernières heures.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /** @test */
    public function corrections_out_and_negative_count_gaps_above_the_threshold_alert_the_admins()
    {
        $seller = User::factory()->frontOffice()->create();
        $archived = User::factory()->backOffice()->create(['archived_at' => now()]);

        // 5 wax cassés (5 × 6 000) et 10 savons manquants à l'inventaire (10 × 2 000) : 50 000
        $this->stock->correct($this->wax->id, 'out', 5, 'casse');
        $this->stock->adjustTo($this->savon->id, 40, 'Inventaire INV-20261021-ABCD', MovementSource::inventory(1));

        // 50 000 sur 700 000 avant les pertes : 7,14 %
        $this->artisan('stock:monitor-drop', ['--threshold' => 5])
            ->expectsOutput('⚠️ Pertes non expliquées : 2 sortie(s), 50,000.00 MGA (7.14 % de la valeur du stock). Envoi des alertes...')
            ->assertExitCode(0);

        Mail::assertSent(StockDropAlert::class, 1);
        Mail::assertSent(StockDropAlert::class, function (StockDropAlert $mail) {
            $this->assertTrue($mail->hasTo('admin@example.com'));
            $this->assertEquals(50000, $mail->lossValue);
            $this->assertEquals(7.14, $mail->lossPercentage);
            $this->assertSame(['Correction : casse', 'Inventaire INV-20261021-ABCD'], array_column($mail->lines, 'reason'));
            $this->assertEquals([30000, 20000], array_column($mail->lines, 'value'));

            return true;
        });
        Mail::assertNotSent(StockDropAlert::class, fn ($mail) => $mail->hasTo($seller->email) || $mail->hasTo($archived->email));
    }

    /** @test */
    public function losses_below_the_threshold_are_reported_without_email()
    {
        $this->stock->correct($this->wax->id, 'out', 5, 'casse');

        $this->artisan('stock:monitor-drop', ['--threshold' => 10])
            // 30 000 sur 700 000 : 4,29 %
            ->expectsOutput("✅ Pertes non expliquées : 1 sortie(s), 30,000.00 MGA (4.29 % de la valeur du stock). Seuil d'alerte : 10 %.")
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /** @test */
    public function gains_and_losses_outside_the_period_are_ignored()
    {
        // Avant-hier : perte hors de la période surveillée
        $this->travel(-2)->days();
        $this->stock->correct($this->wax->id, 'out', 50, 'casse');
        $this->travelBack();

        // Aujourd'hui : entrées seulement (correction en entrée, surplus d'inventaire)
        $this->stock->correct($this->wax->id, 'in', 5, 'retour client');
        $this->stock->adjustTo($this->savon->id, 60, 'Inventaire INV-20261021-ABCD', MovementSource::inventory(1));

        $this->artisan('stock:monitor-drop', ['--threshold' => 0])
            ->expectsOutput('✅ Aucune perte non expliquée sur les 24 dernières heures.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /** @test */
    public function the_alert_email_lists_the_losses()
    {
        $this->stock->correct($this->wax->id, 'out', 5, 'casse');
        $losses = $this->stock->getUnexplainedLosses(now()->subDay());

        (new StockDropAlert($losses['lines'], $losses['value'], $losses['percentage'], 24))
            ->assertSeeInHtml('Pertes de stock non expliquées')
            ->assertSeeInHtml('Correction : casse')
            ->assertSeeInHtml('30,000.00');
    }
}
