<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // 1. Création des Utilisateurs
        $admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@stock.com',
            'password' => Hash::make('password'),
            'role' => 'back_office',
        ]);

        $vendeur = User::create([
            'name' => 'Vendeur Boutique',
            'email' => 'vendeur@stock.com',
            'password' => Hash::make('password'),
            'role' => 'front_office',
        ]);
    }
}
