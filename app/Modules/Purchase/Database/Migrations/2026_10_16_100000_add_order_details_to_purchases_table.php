<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bon de commande : référence du document fournisseur (n° de facture ou de bon de livraison) et notes libres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_reference', 100)->nullable()->after('reference');
            $table->text('notes')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['supplier_reference', 'notes']);
        });
    }
};
