<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auteur et origine de chaque mouvement de stock, au lieu de les deviner dans le texte du motif.
 * - user_id : qui a fait l'opération (vide pour une tâche planifiée ou un ancien mouvement). Comme le reste
 *   de l'historique, un compte cité ne peut plus être supprimé (ON DELETE RESTRICT) : il s'archive.
 * - source_type / source_id : document à l'origine (voir App\Modules\Stock\Support\MovementSource).
 *
 * Les mouvements déjà enregistrés retrouvent leur origine à partir des motifs écrits jusqu'ici.
 * Leur auteur n'a pas été conservé : il reste vide.
 */
return new class extends Migration
{
    /** type => [début du motif, table du document, motif complet à partir de la référence] */
    private const SOURCES = [
        'sale' => ['Vente %', 'sales', "'Vente ' || sales.reference"],
        'purchase' => ['Réception Achat #%', 'purchases', "'Réception Achat #' || purchases.reference"],
        'inventory' => ['Inventaire %', 'inventories', "'Inventaire ' || inventories.reference"],
    ];

    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('stock_after')->constrained('users')->restrictOnDelete();
            $table->string('source_type', 30)->nullable()->after('user_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id']);
        });

        foreach (self::SOURCES as $type => [$pattern, $documents, $reason]) {
            DB::table('stock_movements')->whereNull('source_type')->where('reason', 'like', $pattern)->update([
                'source_type' => $type,
                'source_id' => DB::raw("(SELECT {$documents}.id FROM {$documents} WHERE {$reason} = stock_movements.reason)"),
            ]);
        }

        // Réception annulée : l'achat a été supprimé, il n'y a plus de document à citer
        DB::table('stock_movements')->whereNull('source_type')->where('reason', 'like', 'Annulation Réception Achat #%')
            ->update(['source_type' => 'purchase']);
        DB::table('stock_movements')->whereNull('source_type')->where('reason', 'like', 'Correction : %')
            ->update(['source_type' => 'correction']);
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
