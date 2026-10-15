<?php

namespace App\Core\Http\Controllers;

use App\Core\Services\SettingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index()
    {
        $settings = $this->settingService->getAllSettings();

        return view('core::settings.index', compact('settings'));
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
}
