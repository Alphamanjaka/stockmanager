<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprimer une catégorie, une couleur ou une marque ne doit plus jamais effacer de produits :
 * ces clés passent en ON DELETE RESTRICT. Seul un élément que rien n'utilise peut être supprimé ;
 * les autres s'archivent.
 *
 * (Avant : supprimer une catégorie supprimait ses produits en cascade, puis leurs variantes, leurs
 * mouvements de stock et leurs lignes d'achat.)
 * product_colors.product_id reste en cascade : les variantes font partie du produit, et leurs propres
 * historiques (mouvements, achats, ventes, inventaires) sont protégés par leurs clés.
 */
return new class extends Migration
{
    /** Table => [colonne => [table référencée, comportement d'origine]] */
    private const KEYS = [
        'products' => ['category_id' => ['categories', 'cascade']],
        'product_colors' => [
            'color_id' => ['colors', 'cascade'],
            'brand_id' => ['brands', 'set null'],
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
