<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;

class RunBackupCommand extends Command
{
    // Ajout de l'option --now pour forcer l'exécution immédiate
    protected $signature = 'app:run-backup {--now : Lance la sauvegarde immédiatement sans file d\'attente}';

    // La description affichée avec php artisan list
    protected $description = 'Lance le processus de sauvegarde (Fichiers + Base de données)';

    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    public function handle()
    {
        $this->info('Initialisation du processus de sauvegarde...');

        $runSynchronously = $this->option('now');

        try {
            if ($runSynchronously) {
                $this->info('Mode synchrone activé : La sauvegarde démarre immédiatement (cela peut prendre du temps)...');
                $this->backupService->runBackup(false);

                $this->info('Sortie de la commande backup:run :');
                $this->line(Artisan::output());
                $this->info('✅ Sauvegarde terminée avec succès !');
            } else {
                $this->backupService->runBackup(true);
                $this->info('✅ La demande de sauvegarde a été ajoutée à la file d\'attente.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la sauvegarde : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
