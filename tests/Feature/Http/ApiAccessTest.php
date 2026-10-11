<?php

namespace Tests\Feature\Http;

use App\Modules\Catalog\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_cannot_read_the_api()
    {
        $this->getJson('/api/global-search?q=ab')->assertUnauthorized();
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->getJson('/api/products/1/movements')->assertUnauthorized();
    }

    /** @test */
    public function guests_cannot_delete_a_category_through_the_api()
    {
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();

        $this->assertModelExists($category);
    }

    /** @test */
    public function front_office_users_are_forbidden()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->getJson('/api/global-search?q=ab')->assertForbidden();
        $this->getJson('/api/categories')->assertForbidden();
    }

    /** @test */
    public function back_office_users_can_use_the_api()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->getJson('/api/global-search?q=ab')->assertOk();
        $this->getJson('/api/categories')->assertOk();
    }
}
