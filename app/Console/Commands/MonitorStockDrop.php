<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockService;
use Illuminate\Support\Facades\Mail;
use App\Mail\StockDropAlert;
use App\Models\User;

class MonitorStockDrop extends Command
{
    /**
     * La signature de la commande.
     * Exemple d'utilisation : php artisan stock:monitor-drop --threshold=15
     */
    protected $signature = 'stock:monitor-drop {--threshold=10 : Pourcentage de chute pour déclencher l\'alerte}';

    protected $description = 'Surveille la valeur du stock et envoie une alerte en cas de chute brutale.';

    protected $stockService;

    public function __construct(StockService $stockService)
    {
        parent::__construct();
        $this->stockService = $stockService;
    }

    public function handle()
    {
        $threshold = (float) $this->option('threshold');

        // On récupère l'évolution sur 2 jours via le service existant
        // Index 0 = Hier (Fin de journée), Index 1 = Aujourd'hui (Instant T)
        $evolution = $this->stockService->getTotalStockValueEvolution(2);

        if (count($evolution) < 2) {
            $this->info('Pas assez de données historiques pour l\'analyse.');
            return;
        }

        $previousValue = $evolution[0]['value'];
        $currentValue = $evolution[1]['value'];

        if ($previousValue == 0) {
            return; // Évite la division par zéro
        }

        // Calcul du pourcentage de variation
        $variation = (($currentValue - $previousValue) / $previousValue) * 100;

        // Si la variation est négative (chute) et dépasse le seuil (en valeur absolue)
        if ($variation < 0 && abs($variation) >= $threshold) {
            $this->warn("⚠️ Chute de " . number_format($variation, 2) . "% détectée ! Envoi des alertes...");

            // Envoi aux administrateurs (utilisateurs avec le rôle 'back_office')
            $admins = User::where('role', 'back_office')->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new StockDropAlert($currentValue, $previousValue, $variation));
                // Pause de 2 secondes entre chaque email pour éviter le "Rate Limit" de Mailtrap
                sleep(2);
            }
        } else {
            $this->info("✅ Variation normale (" . number_format($variation, 2) . "%). Seuil d'alerte : -{$threshold}%");
        }
    }
}
