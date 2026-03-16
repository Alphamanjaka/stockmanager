<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class RunBackupCommand extends Command
{
    // Le nom que vous taperez dans le terminal
    protected $signature = 'app:run-backup';

    // La description affichée avec php artisan list
    protected $description = 'Lance la sauvegarde via le BackupService';

    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    public function handle()
    {
        $this->info('Début de la sauvegarde...');

        try {
            // On utilise la méthode de votre service
            $this->backupService->runBackup();
            $this->info('La sauvegarde a été lancée avec succès !');
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
        }
    }
}
