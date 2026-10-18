<?php

namespace Tests\Unit\Database;

use App\Core\Database\PostgresConnector;
use PHPUnit\Framework\TestCase;

class PostgresConnectorTest extends TestCase
{
    /**
     * Construit le DSN via un sous-type qui expose la méthode protégée.
     */
    private function dsn(array $config): string
    {
        $connector = new class extends PostgresConnector
        {
            public function buildDsn(array $config): string
            {
                return $this->getDsn($config);
            }
        };

        return $connector->buildDsn($config);
    }

    public function test_it_builds_a_simple_dsn(): void
    {
        $dsn = $this->dsn(['host' => '127.0.0.1', 'port' => '5432', 'database' => 'app']);

        $this->assertSame("pgsql:host=127.0.0.1;dbname='app';port=5432", $dsn);
    }

    public function test_it_keeps_a_comma_separated_host_list(): void
    {
        $dsn = $this->dsn(['host' => 'node1,node2', 'port' => '5432', 'database' => 'app']);

        $this->assertStringContainsString('host=node1,node2', $dsn);
    }

    public function test_it_appends_the_high_availability_options(): void
    {
        $dsn = $this->dsn([
            'host' => 'node1,node2',
            'port' => '5432',
            'database' => 'app',
            'target_session_attrs' => 'read-write',
            'connect_timeout' => 5,
        ]);

        $this->assertStringContainsString('target_session_attrs=read-write', $dsn);
        $this->assertStringContainsString('connect_timeout=5', $dsn);
    }

    public function test_it_omits_empty_high_availability_options(): void
    {
        $dsn = $this->dsn([
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'app',
            'target_session_attrs' => null,
            'connect_timeout' => null,
        ]);

        $this->assertStringNotContainsString('target_session_attrs', $dsn);
        $this->assertStringNotContainsString('connect_timeout', $dsn);
    }
}
