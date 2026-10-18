<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archivage des contacts (App\Core\Models\Concerns\Archivable) : un fournisseur ou un client
 * avec qui l'on ne travaille plus est archivé ; ses achats ou ses ventes restent complets. NULL = en service.
 */
return new class extends Migration
{
    private const TABLES = ['suppliers', 'customers'];

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
