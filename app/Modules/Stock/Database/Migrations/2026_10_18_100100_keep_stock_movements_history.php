<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'historique du stock ne s'efface jamais : une variante qui a des mouvements ne peut plus être
 * supprimée (ON DELETE RESTRICT au lieu de CASCADE). Le produit s'archive à la place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_color_id']);
            $table->foreign('product_color_id')->references('id')->on('product_colors')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_color_id']);
            $table->foreign('product_color_id')->references('id')->on('product_colors')->cascadeOnDelete();
        });
    }
};
