<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archivage du catalogue (App\Core\Models\Concerns\Archivable) : un produit, une catégorie, une marque
 * ou une couleur qui ne sert plus est archivé au lieu d'être supprimé. NULL = en service.
 */
return new class extends Migration
{
    private const TABLES = ['products', 'categories', 'brands', 'colors'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['archived_at']);
                $table->dropColumn('archived_at');
            });
        }
    }
};
