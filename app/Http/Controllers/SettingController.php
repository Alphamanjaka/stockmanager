<?php

namespace App\Http\Controllers;

use App\Services\{SettingService, BackupService};
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
        $backups = $this->backupService->getBackups();
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
            return redirect()->route('admin.settings.index')
                ->with('error', 'Impossible de lancer la sauvegarde. Veuillez consulter les logs.' . $e->getMessage());
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
