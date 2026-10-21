<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'ancien essai src/Domain (supprimé) créait les tables « inventories » et « product_variants »
 * avec un autre schéma. Une base qui les a encore ne peut pas créer les tables du module Inventaire
 * (« relation inventories already exists »).
 *
 * Exécutée juste avant 2026_10_04_110000_create_inventories_tables :
 * - table de l'ancien essai vide → supprimée ;
 * - table contenant des données → renommée en legacy_<table> (rien n'est perdu) ;
 * - les lignes des anciennes migrations, dont les fichiers n'existent plus, sont retirées de « migrations ».
 * Sans effet sur une base qui n'a jamais eu ces tables.
 */
return new class extends Migration
{
    /** Table de l'ancien essai => colonne qui la distingue d'une table actuelle du même nom. */
    private const LEGACY_TABLES = [
        'inventories' => 'quantity_available',
        'product_variants' => 'attributes',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_TABLES as $table => $legacyColumn) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $legacyColumn) || Schema::hasColumn($table, 'status')) {
                continue;
            }

            if (DB::table($table)->doesntExist()) {
                Schema::drop($table);

                continue;
            }

            Schema::rename($table, "legacy_{$table}");
            if (DB::getDriverName() === 'pgsql') {
                // PostgreSQL ne renomme ni les index ni la séquence : leurs noms bloqueraient la nouvelle table
                foreach (["{$table}_pkey", "{$table}_sku_unique", 'variants_attributes_gin'] as $index) {
                    DB::statement("ALTER INDEX IF EXISTS {$index} RENAME TO legacy_{$index}");
                }
                DB::statement("ALTER SEQUENCE IF EXISTS {$table}_id_seq RENAME TO legacy_{$table}_id_seq");
            }
        }

        DB::table('migrations')->whereIn('migration', [
            '2026_01_01_000001_create_product_variants_table',
            '2026_01_01_000002_create_inventories_table',
        ])->delete();
    }

    public function down(): void
    {
        // Rien à restaurer : les tables de l'ancien essai ne sont plus utilisées par le code.
    }
};
