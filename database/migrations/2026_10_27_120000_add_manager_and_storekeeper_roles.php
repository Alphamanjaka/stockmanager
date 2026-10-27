<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deux rôles de plus pour le back office : gérant (manager) et magasinier (storekeeper), voir App\Core\Support\Role.
 * La colonne users.role est une énumération : sous PostgreSQL, une contrainte CHECK, élargie ici.
 * Les comptes existants gardent leur rôle.
 */
return new class extends Migration
{
    private const ROLES = ['front_office', 'storekeeper', 'manager', 'back_office'];

    private const OLD_ROLES = ['front_office', 'back_office'];

    public function up(): void
    {
        $this->allow(self::ROLES);
    }

    public function down(): void
    {
        // Les gérants et magasiniers redeviennent administrateurs : aucun compte ne perd l'accès au back office
        DB::table('users')->whereIn('role', ['manager', 'storekeeper'])->update(['role' => 'back_office']);
        $this->allow(self::OLD_ROLES);
    }

    private function allow(array $roles): void
    {
        $list = implode(', ', array_map(fn (string $role) => "'{$role}'", $roles));

        switch (DB::getDriverName()) {
            case 'pgsql':
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
                DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$list}))");
                break;
            case 'mysql':
            case 'mariadb':
                DB::statement("ALTER TABLE users MODIFY role ENUM({$list}) NOT NULL DEFAULT 'front_office'");
                break;
                // SQLite : la contrainte ne se modifie pas sans recréer la table ; elle reste en l'état
        }
    }
};
