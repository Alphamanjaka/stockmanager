<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des exécutions de sauvegarde (archive ZIP).
     */
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();

            // 'archive' (ZIP Spatie)
            $table->string('type');

            // 'running', 'success' ou 'failed'
            $table->string('status');

            // Destination : nom du fichier ZIP
            $table->string('target')->nullable();

            $table->text('message')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
