<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un achat reste complet : ni sa variante ni son fournisseur ne peuvent être supprimés
 * tant qu'il les cite (ON DELETE RESTRICT). Ils s'archivent à la place.
 *
 * Avant : supprimer une variante effaçait ses lignes d'achat (CASCADE) et supprimer un fournisseur
 * laissait ses achats sans fournisseur (SET NULL, voir 2026_10_11_100000).
 */
return new class extends Migration
{
    /** Table => [colonne => [table référencée, comportement d'origine]] */
    private const KEYS = [
        'purchase_items' => ['product_color_id' => ['product_colors', 'cascade']],
        'purchases' => ['supplier_id' => ['suppliers', 'set null']],
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
