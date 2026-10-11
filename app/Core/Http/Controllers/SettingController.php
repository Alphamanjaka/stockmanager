<?php

namespace App\Core\Http\Controllers;

use App\Core\Services\SettingService;
use App\Core\Services\BackupService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    protected SettingService $settingService;
    protected BackupService $backupService;

    public function __construct(SettingService $settingService, BackupService $backupService)
    {
        $this->settingService = $settingService;
        $this->backupService = $backupService;
    }

    public function index()
    {
        $settings = $this->settingService->getAllSettings();
        $backups = $this->backupService->getBackups();
        return view('core::settings.index', compact('settings', 'backups'));
    }

    public function update(Request $request, $setting)
    {
        // Seules les clés connues sont enregistrées, et le logo doit être une image
        // (un fichier quelconque finissait sur le disque public).
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',
            'company_siret' => 'nullable|string|max:50',
            'company_vat' => 'nullable|string|max:50',
            'company_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'currency_symbol' => 'nullable|string|max:10',
            'currency_position' => 'nullable|in:before,after',
            'timezone' => 'nullable|timezone',
            'date_format' => 'nullable|in:d/m/Y,Y-m-d,m/d/Y',
            'global_alert_threshold' => 'nullable|integer|min:0',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'stock_valuation_method' => 'nullable|in:FIFO,CUMP',
            'pagination_per_page' => 'nullable|integer|min:1|max:100',
            'theme_mode' => 'nullable|in:light,dark,auto',
        ]);

        $this->settingService->updateSettings($validated);

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
