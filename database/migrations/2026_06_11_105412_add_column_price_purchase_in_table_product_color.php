<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            // add column price_purchase
            $table->decimal('price_purchase', 10, 2)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            // drop column price_purchase
            $table->dropColumn('price_purchase');
        });
    }
};