<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Encaissement en caisse : mode de paiement et somme remise par le client (la monnaie rendue en découle).
            // Les ventes antérieures restent sans montant reçu.
            $table->string('payment_method', 20)->default('cash')->after('total_net');
            $table->decimal('amount_received', 20, 2)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'amount_received']);
        });
    }
};
