<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class BackupService
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
     *
     * @return array
     */
    public function getBackups(): array
    {
        $backups = [];

        if (!$this->disk->exists($this->backupName)) {
            return [];
        }

        $files = $this->disk->files($this->backupName);

        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => $this->humanFileSize($this->disk->size($file)),
                    'date' => date('d/m/Y H:i:s', $this->disk->lastModified($file)),
                ];
            }
        }

        // Tri par date décroissante (le plus récent en premier)
        return array_reverse($backups);
    }

    /**
     * Lance une nouvelle sauvegarde en arrière-plan.
     */
    public function runBackup(): void
    {
        Artisan::queue('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true
        ]);
    }

    /**
     * Gère le téléchargement d'un fichier de sauvegarde.
     */
    public function downloadBackup(Request $request)
    {
        $path = $request->input('path');

        if ($this->disk->exists($path)) {
            return $this->disk->download($path);
        }

        return null; // Le contrôleur gérera la redirection
    }

    /**
     * Supprime un fichier de sauvegarde.
     *
     * @return bool Vrai si le fichier a été trouvé et supprimé.
     */
    public function deleteBackup(Request $request): bool
    {
        $path = $request->input('path');

        if ($this->disk->exists($path)) {
            return $this->disk->delete($path);
        }

        return false;
    }

    /**
     * Helper pour formater la taille des fichiers.
     *
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    private function humanFileSize($bytes, $decimals = 2): string
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * Vérifie l'intégrité d'un fichier de sauvegarde.
     * Tente d'ouvrir le ZIP et cherche un fichier .sql à l'intérieur.
     */
    public function verifyBackup(string $path): array
    {
        $fullPath = $this->disk->path($path);
        $zip = new \ZipArchive;
        $status = ['valid' => false, 'message' => ''];

        if ($zip->open($fullPath) === TRUE) {
            $hasSql = false;
            // On parcourt les fichiers de l'archive pour trouver le dump SQL
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (str_ends_with($filename, '.sql')) {
                    $hasSql = true;
                    break;
                }
            }
            $zip->close();

            return $hasSql
                ? ['valid' => true, 'message' => "Archive valide : Dump SQL détecté."]
                : ['valid' => false, 'message' => "Archive incomplète : Aucun fichier SQL trouvé."];
        }

        return ['valid' => false, 'message' => "Fichier corrompu : Impossible d'ouvrir l'archive ZIP."];
    }
}
