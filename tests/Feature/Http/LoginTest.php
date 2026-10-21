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

    private function failLogin(string $email, int $times, string $ip = '127.0.0.1'): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('auth.login'), ['email' => $email, 'password' => 'wrong-pass']);
        }
    }

    /** @test */
    public function the_sixth_attempt_within_a_minute_is_refused_even_with_the_right_password()
    {
        User::factory()->backOffice()->create(['email' => 'admin@example.com', 'password' => 'secret-123']);
        $this->failLogin('admin@example.com', 5);

        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringStartsWith('Trop de tentatives de connexion.', session('errors')->first('email'));

        // Le blocage dure une minute après le dernier échec
        $this->travel(61)->seconds();
        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    /** @test */
    public function the_limit_applies_to_one_email_from_one_address()
    {
        User::factory()->backOffice()->create(['email' => 'admin@example.com', 'password' => 'secret-123']);
        User::factory()->frontOffice()->create(['email' => 'vendeur@example.com', 'password' => 'secret-123']);
        // La casse de l'email ne permet pas de contourner la limite
        $this->failLogin('ADMIN@example.com', 5);

        // Même adresse, autre compte : autorisé
        $this->post(route('auth.login'), ['email' => 'vendeur@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('saler.dashboard'));
        $this->post(route('logout'));

        // Même compte, autre adresse : autorisé
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function a_successful_login_resets_the_failure_count()
    {
        User::factory()->backOffice()->create(['email' => 'admin@example.com', 'password' => 'secret-123']);
        $this->failLogin('admin@example.com', 4);

        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123']);
        $this->post(route('logout'));
        $this->failLogin('admin@example.com', 4);

        $this->post(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'secret-123'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
