<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Services\SaleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
     public function run(): void
     {
        $this->call([
    //         UserSeeder::class,
            SupplierSeeder::class,
    //         ProductSeeder::class,
    //         // add sales data
    //         SaleSeeder::class,
      ]);



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

        // 2. Création des Catégories
        $catElectronics = Category::create(['name' => 'Électronique']);
        $catAccessories = Category::create(['name' => 'Accessoires']);

        // 3. Création des 4 Couleurs Statiques
        $colors = [
            ['name' => 'Noir Jet', 'code' => '#000000'],
            ['name' => 'Blanc Arctique', 'code' => '#FFFFFF'],
            ['name' => 'Rouge Passion', 'code' => '#FF0000'],
            ['name' => 'Bleu Nuit', 'code' => '#000080'],
        ];

        $colorModels = [];
        foreach ($colors as $color) {
            $colorModels[] = Color::create($color);
        }

        // 4. Création des 5 Produits Statiques
        $productsData = [
            ['name' => 'iPhone 15 Pro', 'price' => 5000000, 'category_id' => $catElectronics->id],
            ['name' => 'Samsung Galaxy S24', 'price' => 4500000, 'category_id' => $catElectronics->id],
            ['name' => 'MacBook Air M2', 'price' => 7500000, 'category_id' => $catElectronics->id],
            ['name' => 'Casque Sony XM5', 'price' => 1800000, 'category_id' => $catAccessories->id],
            ['name' => 'iPad Air', 'price' => 3500000, 'category_id' => $catElectronics->id],
        ];

        $allVariants = [];

        foreach ($productsData as $data) {
            $product = Product::create($data);

            // Pour chaque produit, on crée les 4 variantes de couleur
            foreach ($colorModels as $color) {
                $variant = ProductColor::create([
                    'product_id' => $product->id,
                    'color_id' => $color->id,
                    'stock' => 50, // Stock initial de 50 pour chaque variante
                    'alert_stock' => 5,
                ]);
                $allVariants[] = $variant;
            }
        }

        // 5. Création de 5 Ventes Statiques via le SaleService
        // On récupère le service via le container pour que la logique de stock s'applique
        $saleService = app(SaleService::class);

        // Vente 1 : 2 iPhones Noirs
        $saleService->createSale([
            ['product_color_id' => $allVariants[0]->id, 'quantity' => 2]
        ], 0, $vendeur->id);

        // Vente 2 : 1 MacBook Blanc
        $saleService->createSale([
            ['product_color_id' => $allVariants[9]->id, 'quantity' => 1]
        ], 100000, $vendeur->id); // Avec remise

        // Vente 3 : Mixte
        $saleService->createSale([
            ['product_color_id' => $allVariants[4]->id, 'quantity' => 1], // Samsung Noir
            ['product_color_id' => $allVariants[15]->id, 'quantity' => 3], // Casque Bleu
        ], 0, $vendeur->id);

        // Vente 4 : 5 iPads Rouges
        $saleService->createSale([
            ['product_color_id' => $allVariants[18]->id, 'quantity' => 5]
        ], 0, $vendeur->id);

        // Vente 5 : 1 iPhone Bleu
        $saleService->createSale([
            ['product_color_id' => $allVariants[3]->id, 'quantity' => 1]
        ], 0, $admin->id);

        $this->command->info('Seeding terminé avec succès !');
        $this->command->info('Utilisateur Admin : admin@stock.com / password');
        $this->command->info('Utilisateur Vendeur : vendeur@stock.com / password');
    }
}
