<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une campagne de comptage physique (tout le stock ou une catégorie)
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        // Une ligne par variante : quantité attendue (photo à la création) et quantité comptée
        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('product_color_id')->constrained('product_colors')->cascadeOnDelete();
            $table->integer('expected_quantity');
            $table->integer('counted_quantity')->nullable();
            // Stock réel au moment de la validation, avant ajustement (l'écart appliqué = compté - cette valeur)
            $table->integer('stock_at_validation')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'product_color_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lines');
        Schema::dropIfExists('inventories');
    }
};
