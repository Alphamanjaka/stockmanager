<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Paiement Mobile Money : opérateur et numéro de transaction, pour rapprocher la vente du relevé
            // de l'opérateur. Vides pour les autres modes de paiement et pour les ventes antérieures.
            $table->string('payment_operator', 20)->nullable()->after('amount_received');
            $table->string('payment_reference', 50)->nullable()->after('payment_operator');
            // Un numéro de transaction ne paie qu'une vente, chez un même opérateur
            $table->unique(['payment_operator', 'payment_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['payment_operator', 'payment_reference']);
            $table->dropColumn(['payment_operator', 'payment_reference']);
        });
    }
};
