<?php

namespace App\Core;

use App\Core\Console\Commands\SendDailySummary;
use App\Core\Exports\UsersExport;
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
        'dashboard' => ['label' => 'backoffice.dashboard', 'icon' => 'bi-speedometer2'],
        'catalog' => ['label' => 'backoffice.spaces.catalog', 'icon' => 'bi-collection'],
        'stock' => ['label' => 'backoffice.spaces.stock', 'icon' => 'bi-box-seam'],
        'purchase' => ['label' => 'backoffice.purchases', 'icon' => 'bi-cart3'],
        'sale' => ['label' => 'backoffice.sales', 'icon' => 'bi-shop'],
        'admin' => ['label' => 'backoffice.administration', 'icon' => 'bi-gear'],
    ];

    protected array $menu = [
        'dashboard' => [
            ['label' => 'backoffice.dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard', 'shortcut' => 'd'],
        ],
        // Résumé quotidien (ventes du jour, envoyé par e-mail) : rangé avec les ventes, après leurs pages
        'sale' => [
            ['label' => 'backoffice.daily_summary', 'icon' => 'bi-envelope-paper', 'route' => 'admin.daily-summary.index', 'order' => 20],
        ],
        'admin' => [
            ['label' => 'backoffice.users', 'icon' => 'bi-people', 'route' => 'admin.users.index', 'shortcut' => 'u'],
            ['label' => 'backoffice.settings', 'icon' => 'bi-sliders', 'route' => 'admin.settings.index', 'shortcut' => 'g'],
            ['label' => 'backoffice.import', 'icon' => 'bi-box-arrow-in-down', 'route' => 'admin.imports.index', 'shortcut' => 'i'],
        ],
    ];

    protected array $commands = [
        SendDailySummary::class,
    ];

    protected array $exports = [
        'users' => UsersExport::class,
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
