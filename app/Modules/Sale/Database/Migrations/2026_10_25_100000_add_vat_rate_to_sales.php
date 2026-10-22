<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taux de TVA figé sur chaque vente, comme les coordonnées et la devise : la facture reste juste si le
 * réglage change ensuite. Vide : vente sans TVA (entreprise non assujettie, ou vente enregistrée avant).
 * Les prix de vente sont TTC : la TVA est comprise dans le total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->after('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
    }
};
