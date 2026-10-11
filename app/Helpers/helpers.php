<?php

use App\Core\Services\SettingService;

if (! function_exists('settings')) {
    /**
     * Get a setting value or the SettingService instance.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|\App\Core\Services\SettingService
     */
    function settings($key = null, $default = null)
    {
        if (is_null($key)) {
            return app(SettingService::class);
        }

        return app(SettingService::class)->get($key, $default);
    }
}
