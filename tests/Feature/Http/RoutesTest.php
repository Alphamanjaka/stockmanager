<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    /** @test */
    public function every_route_points_to_an_existing_controller_method()
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, '@') || ! str_starts_with($action, 'App\\')) {
                continue;
            }
            [$controller, $method] = explode('@', $action);
            if (! method_exists($controller, $method)) {
                $missing[] = implode('|', $route->methods()).' /'.$route->uri()." → {$action}";
            }
        }

        $this->assertSame([], $missing, "Routes vers des méthodes inexistantes :\n".implode("\n", $missing));
    }
}
