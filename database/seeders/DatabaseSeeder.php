<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Services\SaleService;
use App\Services\PurchaseService;
use App\Services\ProductService;
use App\Services\SupplierService;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            // Ajout du SaleSeeder pour les ventes
            SaleSeeder::class,
            // Ajout du PurchaseSeeder pour les achats
            PurchaseSeeder::class,
        ]);

    }
}
