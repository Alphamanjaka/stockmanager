<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les pages n'ont pas besoin des assets compilés (public/build, absent en CI) pour être testées
        $this->withoutVite();
    }
}
