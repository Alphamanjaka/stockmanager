<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\SupplierService;

class SupplierSeeder extends Seeder
{

    /**
     *
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplierService = app(SupplierService::class);
        // crate suppliers
        $supplierService->createSupplier([
                'name' => 'Grossiste Informatique SARL',
            'email' => 'contact@grossiste-info.test',
            'phone' => '034 11 222 33',
            'address' => 'Rue des Tech, Antananarivo'
        ]);


        $supplierService->createSupplier([
            'name' => 'Buro-Top Fournisseurs',
            'email' => 'sales@burotop.test',
            'phone' => '032 55 444 11',
            'address' => 'Avenue de l\'Indépendance'
        ]);

        $supplierService->createSupplier([
            'name' => 'Import Global Madagascar',
            'email' => 'import@global.test',
            'phone' => '020 22 555 99',
            'address' => 'Zone Industrielle Akorondrano'
        ]);
    }
}