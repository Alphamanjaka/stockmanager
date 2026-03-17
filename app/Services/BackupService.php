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
        if (!$this->disk->exists($this->backupName)) {
            return [];
        }

        $files = $this->disk->files($this->backupName);

        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.zip'))
            ->map(fn($file) => [
                'path' => $file,
                'name' => basename($file),
                'size' => $this->humanFileSize($this->disk->size($file)),
                'date' => $this->disk->lastModified($file), // Garder le timestamp pour le tri
            ])
            ->sortByDesc('date') // Tri par timestamp (le plus récent en premier)
            ->map(fn($backup) => array_merge($backup, ['date' => date('d/m/Y H:i:s', $backup['date'])])) // Formater la date pour l'affichage
            ->values() // Réindexer le tableau
            ->all();
    }

    public function cleanOldBackups(): void
    {
        // Supprime les vieilles sauvegardes selon les règles définies dans config/backup.php
        Artisan::call('backup:clean');
    }

    /**
     * Lance une nouvelle sauvegarde en arrière-plan.
     */
    public function runBackup(): void
    {
        // En ne passant aucune option, la commande utilisera les paramètres
        // par défaut définis dans config/backup.php (fichiers + base de données).
        Artisan::queue('backup:run');
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
     * Tente d'ouvrir le ZIP en mode consistence check.
     */
    public function verifyBackup(string $path): array
    {
        if (!$this->disk->exists($path)) {
            return ['valid' => false, 'message' => 'Le fichier de sauvegarde est introuvable.'];
        }

        $fullPath = $this->disk->path($path);
        $zip = new \ZipArchive;

        // 1. Vérification de l'intégrité technique de l'archive
        $resultCode = $zip->open($fullPath, \ZipArchive::CHECKCONS);

        if ($resultCode !== true) {
            $message = match ($resultCode) {
                \ZipArchive::ER_NOZIP => 'Le fichier n\'est pas une archive ZIP valide.',
                \ZipArchive::ER_INCONS => 'Incohérences détectées dans l\'archive ZIP.',
                \ZipArchive::ER_CRC => 'Erreur de CRC. Le fichier est probablement corrompu.',
                default => "Impossible d'ouvrir l'archive (Code d'erreur: {$resultCode}).",
            };
            return ['valid' => false, 'message' => $message];
        }

        // 2. Déterminer si un dump de BDD est attendu en lisant le manifeste
        $manifestContent = $zip->getFromName('manifest.json');
        $isDatabaseBackupExpected = false;

        if ($manifestContent !== false) {
            // Le manifeste existe, on l'utilise comme source de vérité
            $manifest = json_decode($manifestContent, true);
            $isDatabaseBackupExpected = !empty($manifest['databases'] ?? []);
        } else {
            // Fallback pour les anciennes sauvegardes : on se base sur la config actuelle
            $isDatabaseBackupExpected = !empty(config('backup.backup.source.databases'));
        }

        // 3. Vérification de la présence du dump de la base de données
        $hasDbDump = false;
        if ($isDatabaseBackupExpected) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (str_starts_with($zip->getNameIndex($i), 'db-dumps/')) {
                    $hasDbDump = true;
                    break;
                }
            }
        }
        $zip->close();

        if ($isDatabaseBackupExpected && !$hasDbDump) {
            return ['valid' => false, 'message' => 'Archive valide, mais le dump de la base de données est manquant.'];
        }

        // Si on ne s'attendait pas à une BDD, ou si on l'a trouvée, tout va bien.
        return ['valid' => true, 'message' => 'L\'intégrité de l\'archive a été vérifiée avec succès.'];
    }
}
