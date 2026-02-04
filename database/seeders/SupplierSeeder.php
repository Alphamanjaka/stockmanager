<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des fournisseurs spécifiques
        Supplier::create([
            'name' => 'Grossiste Informatique SARL',
            'email' => 'contact@grossiste-info.test',
            'phone' => '034 11 222 33',
            'address' => 'Rue des Tech, Antananarivo'
        ]);

        Supplier::create([
            'name' => 'Buro-Top Fournisseurs',
            'email' => 'sales@burotop.test',
            'phone' => '032 55 444 11',
            'address' => 'Avenue de l\'Indépendance'
        ]);

        Supplier::create([
            'name' => 'Import Global Madagascar',
            'email' => 'import@global.test',
            'phone' => '020 22 555 99',
            'address' => 'Zone Industrielle Akorondrano'
        ]);
    }
}
