<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique(); // Le pont avec le produit
            $table->integer('quantity_available')->default(0);
            // On peut aussi stocker des infos de logistique, comme le lieu de stockage, les seuils d'alerte, etc.
            $table->string('storage_location')->nullable();
            $table->integer('alert_threshold')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
