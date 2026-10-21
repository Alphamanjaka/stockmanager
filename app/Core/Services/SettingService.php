<?php

namespace App\Core\Services;

use App\Core\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    /** Paramètres déjà lus pendant la requête (service partagé : AppServiceProvider) */
    private $all = null;

    /**
     * Get all settings as a key-value array.
     */
    public function getAllSettings()
    {
        return $this->all ??= Cache::rememberForever('settings.all', function () {
            return Setting::all()->pluck('value', 'key');
        });
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key, $default = null)
    {
        // Retrieve from the cached collection instead of a new DB query
        $allSettings = $this->getAllSettings();

        return $allSettings[$key] ?? $default;
    }

    /**
     * Update settings.
     */
    public function updateSettings(array $data)
    {
        foreach ($data as $key => $value) {
            // Skip internal fields
            if (in_array($key, ['_token', '_method'])) {
                continue;
            }

            // Handle File Upload (Logo)
            if ($value instanceof UploadedFile) {
                $this->handleFileUpload($key, $value);

                continue;
            }

            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Clear cache so updates are reflected immediately
        Cache::forget('settings.all');
        $this->all = null;
    }

    protected function handleFileUpload($key, UploadedFile $file): void
    {
        // Delete old file if exists
        $oldFile = $this->get($key);
        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
            Storage::disk('public')->delete($oldFile);
        }

        $path = $file->store('settings', 'public');
        Setting::updateOrCreate(['key' => $key], ['value' => $path]);
    }
}
