<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Délai de livraison du fournisseur, en jours (de la commande à la livraison) : le réapprovisionnement commande
 * de quoi tenir pendant ce délai. Vide : le délai par défaut des paramètres s'applique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_time_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('lead_time_days');
        });
    }
};
