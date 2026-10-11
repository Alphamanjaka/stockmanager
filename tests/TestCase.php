<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : les tests vident la base (RefreshDatabase). Ils refusent donc de tourner sur une base
     * PostgreSQL/MySQL dont le nom ne contient pas « test » — par exemple la base « app » de Docker ou de production.
     * Vérifié ici, avant que la base ne soit réinitialisée.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$connection}.driver");
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if ($driver !== 'sqlite' && !str_contains(strtolower($database), 'test')) {
            throw new \RuntimeException(
                "Tests refusés sur la base « {$database} » ({$driver}) : ils l'effaceraient. "
                . 'Utilisez une base dédiée dont le nom contient « test » (ex. DB_DATABASE=testing) ou SQLite.'
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Les pages n'ont pas besoin des assets compilés (public/build, absent en CI) pour être testées
        $this->withoutVite();
    }
}
