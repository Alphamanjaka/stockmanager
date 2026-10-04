<?php

namespace App\Core;

use App\Core\Console\Commands\RunBackupCommand;
use App\Core\Support\ModuleServiceProvider;

/**
 * Socle de l'application : authentification, utilisateurs, paramètres, sauvegardes,
 * tableaux de bord, recherche globale et import centralisé.
 * Les tableaux de bord, la recherche et l'import lisent les modules métier ; l'inverse est interdit.
 */
class CoreServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'core';

    protected array $commands = [
        RunBackupCommand::class,
    ];
}
