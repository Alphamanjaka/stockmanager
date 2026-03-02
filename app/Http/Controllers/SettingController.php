<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;



class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index()
    {
        $settings = $this->settingService->getAllSettings();
        return view('settings.index', compact('settings'));
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
            // Met la commande de sauvegarde en file d'attente pour une exécution en arrière-plan
            Artisan::queue('backup:run', [
                '--only-db' => true, // Sauvegarde uniquement la base de données
                '--disable-notifications' => true // Désactive les notifications pour cette exécution manuelle
            ]);

            return redirect()->route('admin.settings.index')
                ->with('success', 'La sauvegarde de la base de données a été lancée en arrière-plan.');
        } catch (\Exception $e) {
            \Log::error("Failed to queue backup job: " . $e->getMessage());
            return redirect()->route('admin.settings.index')
                ->with('error', 'Impossible de lancer la sauvegarde. Veuillez consulter les logs.');
        }
    }
}