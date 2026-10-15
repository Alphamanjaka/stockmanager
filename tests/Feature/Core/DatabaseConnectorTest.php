<?php

namespace Tests\Feature\Core;

use App\Core\Database\PostgresConnector;
use Tests\TestCase;

class DatabaseConnectorTest extends TestCase
{
    /** @test */
    public function the_pgsql_connector_is_extended_for_high_availability()
    {
        $this->assertInstanceOf(PostgresConnector::class, app('db.connector.pgsql'));
    }
}
