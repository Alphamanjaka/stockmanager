<?php

namespace App\Modules\Backup\Services;

use App\Modules\Backup\Models\BackupRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Sauvegardes "archive ZIP" (fichiers + dump SQL) via Spatie Laravel Backup.
 *
 * Purement en arrière-plan : lancé par le scheduler (commande app:run-backup) et
 * exécuté par le worker de file d'attente. Aucune interface ne l'expose.
 */
class ArchiveBackupService
{
    private $disk;

    private $backupName;

    public function __construct()
    {
        // On centralise la configuration du disque pour la réutiliser partout
        $this->disk = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
        $this->backupName = config('backup.backup.name');
    }

    /**
     * Récupère et formate la liste des sauvegardes existantes.
     */
    public function getBackups(): array
    {
        if (! $this->disk->exists($this->backupName)) {
            return [];
        }

        $files = $this->disk->files($this->backupName);

        return collect($files)
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->map(fn ($file) => [
                'path' => $file,
                'name' => basename($file),
                'size' => $this->humanFileSize($this->disk->size($file)),
                'date' => $this->disk->lastModified($file), // Garder le timestamp pour le tri
            ])
            ->sortByDesc('date') // Tri par timestamp (le plus récent en premier)
            ->map(fn ($backup) => array_merge($backup, ['date' => date('d/m/Y H:i:s', $backup['date'])])) // Formater la date pour l'affichage
            ->values() // Réindexer le tableau
            ->all();
    }

    /**
     * Lance une nouvelle sauvegarde en arrière-plan.
     *
     * @param  bool  $async  Si true, utilise la queue (défaut). Si false, exécution immédiate.
     */
    public function runBackup(bool $async = true): void
    {
        // Historique : la sauvegarde est "running" tant que Spatie n'a pas émis son événement.
        BackupRun::create([
            'type' => BackupRun::TYPE_ARCHIVE,
            'status' => BackupRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        if ($async) {
            Artisan::queue('backup:run');
        } else {
            Artisan::call('backup:run');
        }
    }

    /**
     * Helper pour formater la taille des fichiers.
     *
     * @param  int  $bytes
     * @param  int  $decimals
     */
    private function humanFileSize($bytes, $decimals = 2): string
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$sz[$factor];
    }
}
