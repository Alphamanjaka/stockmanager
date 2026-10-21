<?php

use App\Core\Services\SettingService;

if (! function_exists('settings')) {
    /**
     * Get a setting value or the SettingService instance.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|SettingService
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
     * $symbol : devise figée d'un document (vente : sales.currency_symbol) au lieu de celle des paramètres.
     */
    function money($amount, int $decimals = 2, ?string $symbol = null): string
    {
        $value = number_format((float) $amount, $decimals);
        $symbol = $symbol ?: settings('currency_symbol', 'MGA');

        return settings('currency_position', 'after') === 'before' ? "{$symbol} {$value}" : "{$value} {$symbol}";
    }
}
