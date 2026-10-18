<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une vente garde son client et son vendeur : ils ne peuvent plus être supprimés tant qu'une vente
 * les cite (ON DELETE RESTRICT au lieu de SET NULL, qui rendait la vente anonyme). Ils s'archivent à la place.
 * (sale_items.product_color_id n'a jamais eu de cascade : une variante vendue ne se supprime pas.)
 */
return new class extends Migration
{
    /** Colonne => table référencée */
    private const KEYS = ['customer_id' => 'customers', 'user_id' => 'users'];

    public function up(): void
    {
        $this->replaceKeys('restrict');
    }

    public function down(): void
    {
        $this->replaceKeys('set null');
    }

    private function replaceKeys(string $onDelete): void
    {
        Schema::table('sales', function (Blueprint $table) use ($onDelete) {
            foreach (self::KEYS as $column => $references) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on($references)->onDelete($onDelete);
            }
        });
    }
};
