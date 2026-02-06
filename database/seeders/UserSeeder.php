<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur Back Office
        User::firstOrCreate(
            ['email' => 'alphamanjaka@gmail.com'],
            [
                'name' => 'Admin BackOffice',
                'password' => bcrypt('password123'),
                'role' => 'back_office',
            ]
        );

        // Créer un utilisateur Front Office
        User::firstOrCreate(
            ['email' => 'frontoffice@example.com'],
            [
                'name' => 'Vendeur FrontOffice',
                'password' => bcrypt('password123'),
                'role' => 'front_office',
            ]
        );

        // Créer 5 utilisateurs aléatoires s'il en manque
        if (User::count() < 7) {
            User::factory(5)->create();
        }
    }
}
