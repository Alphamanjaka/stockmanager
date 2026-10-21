<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avoirs : retour d'articles d'une vente, ou annulation complète (tous les articles rendus).
 * La vente n'est jamais modifiée : l'avoir la complète, remet les articles en stock et compte en négatif
 * dans le chiffre d'affaires le jour où il est fait. Comme le reste de l'historique, rien n'est supprimé
 * en cascade (ON DELETE RESTRICT), sauf les lignes avec leur avoir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // AVO-2026-000001, à la suite par année
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->decimal('total', 20, 2); // Montant remboursé (positif), remise de la vente déduite
            $table->string('refund_method')->nullable(); // Mêmes modes que l'encaissement (Sale::PAYMENT_METHODS)
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->restrictOnDelete();
            $table->foreignId('product_color_id')->constrained('product_colors')->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 20, 2); // Prix et coût de la ligne de vente, recopiés
            $table->decimal('unit_cost', 20, 2)->nullable();
            $table->decimal('subtotal', 20, 2); // Remboursé pour la ligne : quantité × prix, part de remise déduite
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
