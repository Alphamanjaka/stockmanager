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

if (! function_exists('money')) {
    /**
     * Montant formaté avec la devise des paramètres (symbole et position) : money(1500) → "1,500.00 MGA".
     */
    function money($amount, int $decimals = 2): string
    {
        $value = number_format((float) $amount, $decimals);
        $symbol = settings('currency_symbol', 'MGA');

        return settings('currency_position', 'after') === 'before' ? "{$symbol} {$value}" : "{$value} {$symbol}";
    }
}
