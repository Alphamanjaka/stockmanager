<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SettingService
{
    /**
     * Get all settings as a key-value array.
     */
    public function getAllSettings()
    {
        return Cache::rememberForever('settings.all', function () {
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
    }

    protected function handleFileUpload($key, UploadedFile $file)
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
