<?php

namespace App\Http\Controllers;

use App\Services\{SettingService, BackupService};
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $settingService;
    protected $backupService;

    public function __construct(SettingService $settingService, BackupService $backupService)
    {
        $this->settingService = $settingService;
        $this->backupService = $backupService;
    }

    public function index()
    {
        $settings = $this->settingService->getAllSettings();

        // Récupération des sauvegardes existantes
        $backupName = config('backup.backup.name');
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]); // 'local' par défaut

        $backups = [];
        if ($disk->exists($backupName)) {
            $files = $disk->files($backupName);

            foreach ($files as $file) {
                if (substr($file, -4) == '.zip') {
                    $backups[] = [
                        'path' => $file,
                        'name' => basename($file),
                        'size' => $this->humanFileSize($disk->size($file)),
                        'date' => date('d/m/Y H:i:s', $disk->lastModified($file)),
                    ];
                }
            }
            // Tri par date décroissante (le plus récent en premier)
            $backups = array_reverse($backups);
        }

        return view('settings.index', compact('settings', 'backups'));
    }

    public function update(Request $request, $setting)
    {
        // Validate if necessary
        $this->settingService->updateSettings($request->all());

        // Redirect to the admin index route for consistency
        return redirect()->route('admin.settings.index')->with('success', 'Paramètres mis à jour avec succès.');
    }

    public function runBackup()
    {
        try {
            $this->backupService->runBackup();

            return redirect()->route('admin.settings.index')
                ->with('success', 'La sauvegarde de la base de données a été lancée en arrière-plan.');
        } catch (\Exception $e) {
            \Log::error("Failed to queue backup job: " . $e->getMessage());
            return redirect()->route('admin.settings.index')
                ->with('error', 'Impossible de lancer la sauvegarde. Veuillez consulter les logs.');
        }
    }

    public function downloadBackup(Request $request)
    {
        $download = $this->backupService->downloadBackup($request);

        if ($download) {
            return $download;
        }

        return back()->with('error', 'Le fichier de sauvegarde est introuvable.');
    }

    public function deleteBackup(Request $request)
    {
        if ($this->backupService->deleteBackup($request)) {
            return back()->with('success', 'Sauvegarde supprimée avec succès.');
        }

        return back()->with('error', 'Le fichier de sauvegarde est introuvable.');
    }

    public function verifyBackup(Request $request)
    {
        $path = $request->input('path');

        if (!$path) return back()->with('error', 'Fichier non spécifié.');

        $result = $this->backupService->verifyBackup($path);

        return back()->with(
            $result['valid'] ? 'success' : 'error',
            $result['message']
        );
    }
}
