<?php

namespace App\Core;

use App\Core\Navigation\BackOfficeMenu;
use App\Core\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\View;

/**
 * Socle de l'application : authentification, utilisateurs, paramètres,
 * tableaux de bord, recherche globale et import centralisé.
 * Les tableaux de bord, la recherche et l'import lisent les modules métier ; l'inverse est interdit.
 */
class CoreServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'core';

    /** Tuiles du lanceur, dans l'ordre d'affichage. Les modules y rangent leurs pages (propriété $menu). */
    protected array $menuSpaces = [
        'dashboard' => ['label' => 'backoffice.dashboard', 'icon' => 'fa-gauge-high'],
        'catalog' => ['label' => 'backoffice.spaces.catalog', 'icon' => 'fa-boxes-stacked'],
        'stock' => ['label' => 'backoffice.spaces.stock', 'icon' => 'fa-warehouse'],
        'purchase' => ['label' => 'backoffice.purchases', 'icon' => 'fa-cart-flatbed'],
        'sale' => ['label' => 'backoffice.sales', 'icon' => 'fa-cash-register'],
        'admin' => ['label' => 'backoffice.administration', 'icon' => 'fa-gears'],
    ];

    protected array $menu = [
        'dashboard' => [
            ['label' => 'backoffice.dashboard', 'icon' => 'fa-gauge-high', 'route' => 'admin.dashboard', 'shortcut' => 'd'],
        ],
        'admin' => [
            ['label' => 'backoffice.users', 'icon' => 'fa-users', 'route' => 'admin.users.index', 'shortcut' => 'u'],
            ['label' => 'backoffice.settings', 'icon' => 'fa-gear', 'route' => 'admin.settings.index', 'shortcut' => 'g'],
            ['label' => 'backoffice.import', 'icon' => 'fa-file-import', 'route' => 'admin.imports.index', 'shortcut' => 'i'],
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Layout du back office : menu résolu pour la page courante
        View::composer('layouts.app-back-office', function ($view) {
            $view->with('nav', $this->app->make(BackOfficeMenu::class)->resolve(request()));
        });

        // Cloche : nombre de notifications non lues et les plus récentes (pas toutes : la liste peut être longue)
        View::composer('layouts.back-office.tools', function ($view) {
            $user = auth()->user();

            $view->with([
                'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
                'latestNotifications' => $user?->unreadNotifications()->limit(8)->get() ?? collect(),
            ]);
        });
    }
}
