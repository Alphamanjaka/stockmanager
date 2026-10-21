<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Coût d'achat unitaire figé sur chaque ligne de vente, comme le prix : la marge d'une vente ne change plus
 * quand le coût de la variante évolue. Vide quand le coût n'était pas connu (prix d'achat à 0 ou absent).
 *
 * Les ventes déjà enregistrées reçoivent le prix d'achat actuel de leur variante : c'est une estimation
 * (le coût au moment de la vente n'a pas été conservé), mais elle permet de calculer leur marge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 20, 2)->nullable()->after('unit_price');
        });

        DB::table('sale_items')->whereNull('unit_cost')->update([
            'unit_cost' => DB::raw('(SELECT product_colors.price_purchase FROM product_colors
                WHERE product_colors.id = sale_items.product_color_id AND product_colors.price_purchase > 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
