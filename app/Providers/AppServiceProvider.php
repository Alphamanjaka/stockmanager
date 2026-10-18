<?php

namespace App\Providers;

use App\Core\Database\PostgresConnector;
use App\Core\Services\SettingService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Connecteur PostgreSQL étendu : gère la liste d'hôtes (multi-nœuds) et
        // target_session_attrs pour la bascule automatique (pg_auto_failover).
        $this->app->bind('db.connector.pgsql', fn () => new PostgresConnector);

        // Un seul service par requête : les paramètres ne sont lus qu'une fois (settings(), money())
        $this->app->singleton(SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
