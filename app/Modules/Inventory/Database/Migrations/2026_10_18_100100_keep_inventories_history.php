<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un inventaire reste complet : ses variantes comptées, sa catégorie et les personnes qui l'ont ouvert
 * et validé ne peuvent plus être supprimées tant qu'il les cite (ON DELETE RESTRICT).
 * Avant : supprimer une variante effaçait ses lignes d'inventaire, supprimer une catégorie faisait
 * passer l'inventaire pour un inventaire de « tous les produits ».
 */
return new class extends Migration
{
    /** Table => [colonne => [table référencée, comportement d'origine]] */
    private const KEYS = [
        'inventory_lines' => ['product_color_id' => ['product_colors', 'cascade']],
        'inventories' => [
            'category_id' => ['categories', 'set null'],
            'user_id' => ['users', 'set null'],
            'validated_by' => ['users', 'set null'],
        ],
    ];

    public function up(): void
    {
        $this->replaceKeys(fn () => 'restrict');
    }

    public function down(): void
    {
        $this->replaceKeys(fn (string $original) => $original);
    }

    private function replaceKeys(callable $onDelete): void
    {
        foreach (self::KEYS as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns, $onDelete) {
                foreach ($columns as $column => [$references, $original]) {
                    $table->dropForeign([$column]);
                    $table->foreign($column)->references('id')->on($references)->onDelete($onDelete($original));
                }
            });
        }
    }
};
