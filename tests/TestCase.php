<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : les tests vident la base (RefreshDatabase). Ils refusent donc de tourner sur une base
     * dont le nom ne contient pas « test » — par exemple la base de développement « app » ou la production.
     * Vérifié ici, avant que la base ne soit réinitialisée.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if (! str_contains(strtolower($database), 'test')) {
            throw new \RuntimeException(
                "Tests refusés sur la base « {$database} » : ils l'effaceraient. "
                .'Utilisez la base de test (DB_DATABASE=stockmanager_test, voir phpunit.xml).'
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
