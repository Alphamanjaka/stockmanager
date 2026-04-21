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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Ex: VENTE-2026-001
            $table->decimal('total_brut', 10, 2);  // Somme des prix * quantités
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_net', 10, 2);   // total_brut - discount
            $table->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_color_id')->constrained('product_colors');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Prix au moment de la vente
            $table->decimal('subtotal', 10, 2);   // quantity * unit_price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
