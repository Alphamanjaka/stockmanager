<?php

namespace App\Core\Support;

use App\Core\Exports\ExportRegistry;
use App\Core\Navigation\BackOfficeMenu;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base des modules métier (app/Modules/*).
 *
 * Chaque module déclare son nom et hérite du chargement par convention :
 * - Resources/views      → vues accessibles via "<nom>::dossier.vue"
 * - Resources/lang       → traductions du module : fr.json, la clé est le texte anglais (« New brand » → « Nouvelle marque »)
 * - Database/Migrations  → migrations du module
 * - Routes/admin.php     → back office : préfixe /admin, noms "admin.*", auth + ensure.back.office
 * - Routes/saler.php     → front office : préfixe /saler, noms "saler.*", auth + ensure.front.office
 * - Routes/api.php       → appels AJAX du back office : préfixe /api, auth + ensure.back.office
 * - $menuSpaces, $menu   → espaces du lanceur et pages du module dans le menu du back office
 * - $exports             → exports PDF / Excel des listes du module (App\Core\Exports\ListExport)
 *
 * La protection est appliquée ici : une route placée dans le bon fichier ne peut pas être exposée par oubli.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Nom court du module, utilisé comme namespace de vues (ex. 'contact'). */
    protected string $name;

    /** Commandes Artisan fournies par le module. */
    protected array $commands = [];

    /**
     * Écouteurs d'événements : [Evenement::class => [Ecouteur::class, ...]].
     * C'est ainsi qu'un module réagit à un autre sans que celui-ci dépende de lui.
     */
    protected array $listen = [];

    /**
     * Espaces du lanceur du back office : ['clé' => ['label' => 'clé de traduction', 'icon' => 'bi-…']].
     * Le Core déclare les espaces existants ; un nouveau module peut ajouter le sien.
     */
    protected array $menuSpaces = [];

    /**
     * Pages du module dans le menu du back office, rangées par espace : ['clé d'espace' => [entrée, ...]].
     * Format d'une entrée : voir App\Core\Navigation\BackOfficeMenu.
     */
    protected array $menu = [];

    /**
     * Exports PDF / Excel des listes du module : ['clé d'URL' => classe qui étend App\Core\Exports\ListExport].
     * Servis par la route admin.exports.download (admin/exports/{clé}/{pdf|xlsx}).
     */
    protected array $exports = [];

    public function register(): void
    {
        $this->app->singletonIf(BackOfficeMenu::class);
        $this->app->singletonIf(ExportRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->path('Resources/views'), $this->name);
        $this->loadJsonTranslationsFrom($this->path('Resources/lang'));
        $this->loadMigrationsFrom($this->path('Database/Migrations'));

        $menu = $this->app->make(BackOfficeMenu::class);
        $menu->addSpaces($this->menuSpaces);

        foreach ($this->menu as $space => $entries) {
            $menu->addEntries($space, $entries);
        }

        $this->app->make(ExportRegistry::class)->add($this->exports);

        foreach ($this->listen as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        if ($this->commands && $this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $groups = [
            'admin.php' => fn () => Route::middleware(['web', 'auth', 'ensure.back.office'])->prefix('admin')->name('admin.'),
            'saler.php' => fn () => Route::middleware(['web', 'auth', 'ensure.front.office'])->prefix('saler')->name('saler.'),
            'api.php' => fn () => Route::middleware(['web', 'auth', 'ensure.back.office'])->prefix('api'),
        ];

        foreach ($groups as $file => $group) {
            if (is_file($path = $this->path("Routes/{$file}"))) {
                $group()->group($path);
            }
        }
    }

    /** Chemin absolu dans le dossier du module (celui du provider concret). */
    protected function path(string $relative = ''): string
    {
        $dir = dirname((new \ReflectionClass(static::class))->getFileName());

        return $relative === '' ? $dir : "{$dir}/{$relative}";
    }
}
