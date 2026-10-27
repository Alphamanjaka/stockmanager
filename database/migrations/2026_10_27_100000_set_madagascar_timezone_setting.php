<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Le fuseau horaire était initialisé à Europe/Paris, sans effet jusqu'au résumé quotidien qui le suit
 * (heure d'envoi, début et fin de la journée). La boutique est à Madagascar : la valeur initiale passe à
 * Indian/Antananarivo. Un fuseau choisi par l'administrateur n'est pas touché.
 * Les paramètres sont en cache permanent (SettingService) : il est vidé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $current = DB::table('settings')->where('key', 'timezone')->value('value');

        if ($current === null || $current === '' || $current === 'Europe/Paris') {
            DB::table('settings')->updateOrInsert(
                ['key' => 'timezone'],
                ['value' => 'Indian/Antananarivo', 'group' => 'regional', 'type' => 'string', 'updated_at' => now()],
            );
        }

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'timezone')->where('value', 'Indian/Antananarivo')->update(['value' => 'Europe/Paris']);
        Cache::forget('settings.all');
    }
};
