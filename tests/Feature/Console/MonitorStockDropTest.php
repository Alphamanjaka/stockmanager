<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\StockService;
use App\Models\User;
use App\Mail\StockDropAlert;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;

class MonitorStockDropTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste que l'alerte email est envoyée quand la chute de stock dépasse le seuil.
     *
     * @return void
     */
    public function test_sends_alert_on_significant_stock_drop(): void
    {
        // 1. Préparation
        Mail::fake();

        // Créer un utilisateur admin qui recevra l'alerte
        $admin = User::factory()->create(['role' => 'back_office', 'email' => 'admin@test.com']);

        // On simule le StockService pour contrôler sa réponse
        $this->mock(StockService::class, function (MockInterface $mock) {
            // Simule une chute de 1000€ à 800€ (-20%)
            $evolutionData = [
                ['date' => '01/01', 'value' => 1000.00], // Hier
                ['date' => '02/01', 'value' => 800.00],  // Aujourd'hui
            ];
            $mock->shouldReceive('getTotalStockValueEvolution')->with(2)->andReturn($evolutionData);
        });

        // 2. Action
        // On exécute la commande avec un seuil de 10%
        $this->artisan('stock:monitor-drop', ['--threshold' => 10])
            ->expectsOutput('⚠️ Chute de -20.00% détectée ! Envoi des alertes...')
            ->assertExitCode(0);

        // 3. Assertions
        // On vérifie qu'un email a été envoyé à l'admin
        Mail::assertSent(StockDropAlert::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        // On vérifie que l'email contient les bonnes données
        Mail::assertSent(StockDropAlert::class, function ($mail) {
            $this->assertEquals(800.00, $mail->currentValue);
            $this->assertEquals(1000.00, $mail->previousValue);
            $this->assertEquals(-20.00, $mail->dropPercentage);
            return true;
        });
    }

    /**
     * Teste qu'aucun email n'est envoyé si la chute est inférieure au seuil.
     *
     * @return void
     */
    public function test_does_not_send_alert_on_minor_stock_drop(): void
    {
        // 1. Préparation
        Mail::fake();
        User::factory()->create(['role' => 'back_office']);

        // On simule le StockService
        $this->mock(StockService::class, function (MockInterface $mock) {
            // Simule une chute de 1000€ à 950€ (-5%)
            $evolutionData = [
                ['date' => '01/01', 'value' => 1000.00],
                ['date' => '02/01', 'value' => 950.00],
            ];
            $mock->shouldReceive('getTotalStockValueEvolution')->with(2)->andReturn($evolutionData);
        });

        // 2. Action
        // On exécute la commande avec un seuil de 10%
        $this->artisan('stock:monitor-drop', ['--threshold' => 10])
            ->expectsOutput('✅ Variation normale (-5.00%). Seuil d\'alerte : -10%')
            ->assertExitCode(0);

        // 3. Assertions
        // On vérifie qu'AUCUN email n'a été envoyé
        Mail::assertNotSent(StockDropAlert::class);
    }
}
