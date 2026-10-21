<?php

namespace App\Modules\Stock\Console\Commands;

use App\Models\User;
use App\Modules\Stock\Mail\StockDropAlert;
use App\Modules\Stock\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Surveille les pertes non expliquées : corrections en sortie et écarts d'inventaire négatifs.
 * Les ventes et les annulations d'achat ne comptent pas : une bonne journée de ventes ne déclenche plus d'alerte.
 */
class MonitorStockDrop extends Command
{
    /**
     * La signature de la commande.
     * Exemple d'utilisation : php artisan stock:monitor-drop --threshold=2 --hours=24
     */
    protected $signature = 'stock:monitor-drop
        {--threshold=1 : Pertes, en % de la valeur du stock, à partir desquelles l\'alerte est envoyée}
        {--hours=24 : Période surveillée, en heures (24 pour une exécution quotidienne)}';

    protected $description = 'Surveille les pertes de stock non expliquées et alerte les administrateurs.';

    protected $stockService;

    public function __construct(StockService $stockService)
    {
        parent::__construct();
        $this->stockService = $stockService;
    }

    public function handle()
    {
        $threshold = (float) $this->option('threshold');
        $hours = max(1, (int) $this->option('hours'));

        $losses = $this->stockService->getUnexplainedLosses(now()->subHours($hours));

        if (empty($losses['lines'])) {
            $this->info("✅ Aucune perte non expliquée sur les {$hours} dernières heures.");

            return;
        }

        $summary = count($losses['lines']).' sortie(s), '.money($losses['value'])
            .' ('.number_format($losses['percentage'], 2).' % de la valeur du stock)';

        if ($losses['percentage'] < $threshold) {
            $this->info("✅ Pertes non expliquées : {$summary}. Seuil d'alerte : {$threshold} %.");

            return;
        }

        $this->warn("⚠️ Pertes non expliquées : {$summary}. Envoi des alertes...");

        // Envoi aux administrateurs en service (rôle 'back_office', compte non archivé)
        $admins = User::where('role', 'back_office')->active()->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new StockDropAlert($losses['lines'], $losses['value'], $losses['percentage'], $hours));
            // Pause de 2 secondes entre chaque email pour éviter le "Rate Limit" de Mailtrap
            sleep(2);
        }
    }
}
