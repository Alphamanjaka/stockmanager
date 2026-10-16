<?php

namespace App\Modules\Backup;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Backup\Console\Commands\RunBackupCommand;
use App\Modules\Backup\Listeners\MarkArchiveBackupFailed;
use App\Modules\Backup\Listeners\MarkArchiveBackupSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;

/**
 * Module Backup : sauvegarde "archive ZIP" (fichiers + dump SQL) gérée par
 * Spatie Laravel Backup, lancée en tâche de fond (scheduler + file d'attente).
 *
 * La haute disponibilité de la base de données (bascule automatique vers le
 * nœud de secours) est prise en charge par PostgreSQL lui-même, via
 * pg_auto_failover et une connexion multi-hôtes — voir docs/ha-postgresql.md.
 * Aucune vue ni route : le module est purement en arrière-plan.
 */
class BackupServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'backup';

    protected array $commands = [
        RunBackupCommand::class,
    ];

    protected array $listen = [
        // Fin de la sauvegarde d'archive → mise à jour de l'historique.
        BackupWasSuccessful::class => [MarkArchiveBackupSuccessful::class],
        BackupHasFailed::class => [MarkArchiveBackupFailed::class],
    ];
}
