<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pas d'inscription publique : seuls les administrateurs créent des comptes (admin/users).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function there_is_no_public_registration()
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Visiteur',
            'email' => 'visiteur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'visiteur@example.com']);
        $this->get('/login')->assertOk()->assertDontSee('/register');
    }

    /** @test */
    public function an_administrator_creates_accounts()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->post(route('admin.users.store'), [
            'name' => 'Vendeur',
            'email' => 'vendeur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'front_office',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'vendeur@example.com', 'role' => 'front_office']);
    }

    /** @test */
    public function a_seller_cannot_create_accounts()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->post(route('admin.users.store'), [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'back_office',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.com']);
    }
}
