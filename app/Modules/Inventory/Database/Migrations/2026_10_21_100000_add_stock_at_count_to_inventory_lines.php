<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock de la variante au moment où sa ligne est comptée. La validation applique l'écart
 * « compté − stock au comptage » au stock courant : les ventes et réceptions faites entre le comptage
 * et la validation sont conservées (avant, le stock était ramené à la quantité comptée et les effaçait).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_lines', function (Blueprint $table) {
            $table->integer('stock_at_count')->nullable()->after('counted_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_lines', function (Blueprint $table) {
            $table->dropColumn('stock_at_count');
        });
    }
};
