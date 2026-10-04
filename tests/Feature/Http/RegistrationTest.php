<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function public_registration_always_creates_a_front_office_account()
    {
        // Un visiteur tente de s'attribuer le back office en injectant le champ 'role'
        $response = $this->post('/register', [
            'name' => 'Visiteur',
            'email' => 'visiteur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'back_office',
        ]);

        $response->assertRedirect(route('saler.dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'visiteur@example.com',
            'role' => 'front_office',
        ]);
    }

    /** @test */
    public function a_registered_user_cannot_open_the_back_office()
    {
        $this->post('/register', [
            'name' => 'Visiteur',
            'email' => 'visiteur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'back_office',
        ]);

        $this->get('/admin/dashboard')->assertRedirect(route('dashboard'));
        $this->assertFalse(User::where('email', 'visiteur@example.com')->first()->isBackOffice());
    }
}
