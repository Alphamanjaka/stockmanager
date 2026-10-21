<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pas de choix d'espace à la connexion : le rôle du compte décide du tableau de bord.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_login_page_no_longer_asks_for_a_workspace()
    {
        $this->get('/login')->assertOk()->assertDontSee('name="role"', false);
    }

    /** @test */
    public function an_administrator_lands_on_the_back_office()
    {
        User::factory()->backOffice()->create(['email' => 'admin@example.com', 'password' => 'secret-123']);

        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    /** @test */
    public function a_seller_lands_on_the_front_office()
    {
        User::factory()->frontOffice()->create(['email' => 'vendeur@example.com', 'password' => 'secret-123']);

        $this->post(route('auth.login'), ['email' => 'vendeur@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('saler.dashboard'));
        $this->assertAuthenticated();
    }

    /** @test */
    public function a_posted_role_cannot_override_the_account_role()
    {
        User::factory()->frontOffice()->create(['email' => 'vendeur@example.com', 'password' => 'secret-123']);

        $this->post(route('auth.login'), ['email' => 'vendeur@example.com', 'password' => 'secret-123', 'role' => 'back_office'])
            ->assertRedirect(route('saler.dashboard'));
    }

    /** @test */
    public function wrong_credentials_are_rejected()
    {
        User::factory()->backOffice()->create(['email' => 'admin@example.com', 'password' => 'secret-123']);

        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'wrong-pass'])
            ->assertSessionHasErrors(['email' => 'Les identifiants fournis sont incorrects.']);
        $this->assertGuest();
    }
}
