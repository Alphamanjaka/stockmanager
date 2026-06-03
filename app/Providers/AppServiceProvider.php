<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        // On charge les migrations des différents domaines
        $this->loadMigrationsFrom(base_path('src/Domain/Catalog/Database/Migrations'));
        $this->loadMigrationsFrom(base_path('src/Domain/Stock/Database/Migrations'));
    }
}
