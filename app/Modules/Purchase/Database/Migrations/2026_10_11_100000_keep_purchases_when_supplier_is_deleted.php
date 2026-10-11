<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprimer un fournisseur ne doit jamais effacer ses achats : la clé étrangère passe
 * de ON DELETE CASCADE à ON DELETE SET NULL (les achats restent, sans fournisseur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }
};
