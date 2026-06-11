<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Pas de contrainte FK stricte si on veut du découplage total, ou garde-la si tu es dans la même base
            $table->decimal('price', 10, 2);  //
            
            // Notre fameux champ semi-structuré PostgreSQL
            $table->jsonb('attributes')->nullable();

            $table->timestamps();

            // Index GIN pour des recherches ultra-rapides dans le JSONB
            $table->index('attributes', 'variants_attributes_gin', 'gin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};