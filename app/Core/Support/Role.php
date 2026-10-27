<?php

namespace App\Core\Support;

use Illuminate\Support\Str;

/**
 * Rôles des comptes et pages du back office qu'ils peuvent ouvrir.
 *
 * - Administrateur (back_office) : tout le back office.
 * - Gérant (manager) : tout le back office sauf les utilisateurs, les paramètres, l'import et l'envoi du résumé
 *   quotidien : il suit l'activité sans pouvoir changer la configuration ni les accès.
 * - Magasinier (storekeeper) : réceptions des achats, inventaires, mouvements et corrections de stock, produits en
 *   lecture et étiquettes. Ni ventes, ni bons de commande, ni tableau de bord.
 * - Vendeur (front_office) : la caisse (front office) seulement.
 *
 * Les droits portent sur les noms de routes (motifs Str::is) et sont vérifiés par EnsureBackOfficeAccess pour
 * toutes les routes admin.php et api.php des modules : une route qu'aucun motif n'autorise est refusée.
 * Un export (admin.exports.download) se vérifie sous le nom « admin.exports.<liste> ».
 */
final class Role
{
    public const ADMIN = 'back_office';

    public const MANAGER = 'manager';

    public const STOREKEEPER = 'storekeeper';

    public const SELLER = 'front_office';

    /** Dans l'ordre des listes de choix. */
    public const ALL = [self::SELLER, self::STOREKEEPER, self::MANAGER, self::ADMIN];

    /** Rôles qui travaillent dans le back office. */
    public const BACK_OFFICE = [self::ADMIN, self::MANAGER, self::STOREKEEPER];

    /** Rôles qui suivent l'activité : alertes et résumé quotidien. */
    public const SUPERVISORS = [self::ADMIN, self::MANAGER];

    /** Routes autorisées, par rôle (l'administrateur a tout). */
    private const ALLOWED = [
        self::MANAGER => ['*'],
        self::STOREKEEPER => [
            'admin.purchases.index', 'admin.purchases.get-purchases-api', 'admin.purchases.show',
            'admin.purchases.receive', 'admin.purchases.receive.store', 'admin.purchases.pdf', 'admin.purchases.pdf.preview',
            'admin.inventories.*',
            'admin.movements.*',
            'admin.products.index', 'admin.products.show', 'api.products.*',
            'admin.labels.*',
            'admin.notifications.*',
            'admin.exports.purchases', 'admin.exports.inventories', 'admin.exports.movements', 'admin.exports.products',
        ],
    ];

    /** Routes refusées malgré un motif autorisé. */
    private const DENIED = [
        self::MANAGER => [
            'admin.users.*', 'admin.exports.users',
            'admin.settings.*',
            'admin.imports.*',
            'admin.daily-summary.settings', 'admin.daily-summary.test',
        ],
    ];

    /** Première page après la connexion. */
    private const LANDING = [
        self::ADMIN => 'admin.dashboard',
        self::MANAGER => 'admin.dashboard',
        self::STOREKEEPER => 'admin.purchases.index',
        self::SELLER => 'saler.dashboard',
    ];

    public static function isBackOffice(?string $role): bool
    {
        return in_array($role, self::BACK_OFFICE, true);
    }

    /** Le rôle peut-il ouvrir la route $name du back office ? */
    public static function allows(?string $role, ?string $name): bool
    {
        if ($role === self::ADMIN) {
            return true;
        }

        if ($name === null || ! self::isBackOffice($role)) {
            return false;
        }

        return Str::is(self::ALLOWED[$role] ?? [], $name) && ! Str::is(self::DENIED[$role] ?? [], $name);
    }

    public static function landingRoute(?string $role): string
    {
        return self::LANDING[$role] ?? 'saler.dashboard';
    }

    /** Libellé affiché (lang/xx/backoffice.php, clé roles). */
    public static function label(?string $role): string
    {
        return in_array($role, self::ALL, true) ? __("backoffice.roles.{$role}") : (string) $role;
    }
}
