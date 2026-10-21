<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Réception partielle : chaque ligne d'achat garde la quantité déjà reçue, et un achat peut être
 * « partiellement reçu » (une partie livrée, le reste attendu).
 *
 * Les achats déjà reçus l'ont été en entier : leurs lignes sont marquées reçues en totalité.
 */
return new class extends Migration
{
    private const STATES = ['Draft', 'Ordered', 'PartiallyReceived', 'Received'];

    private const PREVIOUS_STATES = ['Draft', 'Ordered', 'Received'];

    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->default(0)->after('quantity');
        });

        DB::table('purchase_items')
            ->whereIn('purchase_id', DB::table('purchases')->where('state', 'Received')->select('id'))
            ->update(['received_quantity' => DB::raw('quantity')]);

        $this->allowStates(self::STATES);
    }

    public function down(): void
    {
        // Un achat partiellement reçu redevient « commandé » : le reste n'est plus suivi
        DB::table('purchases')->where('state', 'PartiallyReceived')->update(['state' => 'Ordered']);
        $this->allowStates(self::PREVIOUS_STATES);

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }

    /** Valeurs acceptées par la colonne state (enum : contrainte CHECK sous PostgreSQL). */
    private function allowStates(array $states): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $values = implode(', ', array_map(fn (string $state) => "'{$state}'", $states));
            DB::statement('ALTER TABLE purchases DROP CONSTRAINT IF EXISTS purchases_state_check');
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_state_check CHECK (state IN ({$values}))");

            return;
        }

        Schema::table('purchases', function (Blueprint $table) use ($states) {
            $table->enum('state', $states)->default('Draft')->change();
        });
    }
};
