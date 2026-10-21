<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Le paramètre « Méthode de valorisation » (FIFO ou CUMP) n'avait aucun effet. Le stock est maintenant
 * valorisé au coût moyen pondéré (CUMP), recalculé à chaque réception : FIFO n'est pas calculé,
 * le choix disparaît. Les paramètres sont en cache permanent (SettingService) : il est vidé.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'stock_valuation_method')->delete();
        Cache::forget('settings.all');
    }

    public function down(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key' => 'stock_valuation_method', 'value' => 'FIFO', 'group' => 'stock', 'type' => 'string',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Cache::forget('settings.all');
    }
};
