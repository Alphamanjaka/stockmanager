<?php

namespace App\Core;

use App\Core\Support\ModuleServiceProvider;

/**
 * Socle de l'application : authentification, utilisateurs, paramètres,
 * tableaux de bord, recherche globale et import centralisé.
 * Les tableaux de bord, la recherche et l'import lisent les modules métier ; l'inverse est interdit.
 */
class CoreServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'core';
}
