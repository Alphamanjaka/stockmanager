<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Services\SaleService;
use App\Services\PurchaseService;

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
        ]);

        // Create 100 products
        \App\Models\Product::factory(50)->create();

        // Create a specific user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

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

        $products = \App\Models\Product::all();

        if ($products->isEmpty()) {
            $this->command->info('Skipping sale/purchase seeding because no products found.');
            return;
        }

        // --- SEEDING DES VENTES (SALES) ---
        $saleService = app(SaleService::class);

        // On crée 30 ventes complètes
        for ($i = 0; $i < 30; $i++) {
            $saleItems = [];
            // Pour chaque vente, on prend entre 1 et 5 produits au hasard
            $productsToSell = $products->random(rand(1, 5));

            foreach ($productsToSell as $product) {
                // Pour les besoins du seeder, on s'assure de ne pas vendre plus que le stock initial
                $quantity = rand(1, min(5, $product->quantity_stock));
                if ($quantity <= 0) continue;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ];
            }

            if (empty($saleItems)) continue;

            $discount = rand(0, 1) ? rand(5, 50) : 0; // Une chance sur deux d'avoir une remise
            $saleService->processSale($saleItems, $discount);
        }

        // --- SEEDING DES ACHATS (PURCHASES) ---
        $purchaseService = app(PurchaseService::class);
        $suppliers = Supplier::all();

        if ($products->isEmpty() || $suppliers->isEmpty()) {
            $this->command->info('Skipping purchase seeding because no products or suppliers found.');
            return;
        }

        // On crée 15 achats complets
        for ($i = 0; $i < 15; $i++) {
            $purchaseItems = [];
            // Pour chaque achat, on prend entre 1 et 5 produits au hasard
            $productsToPurchase = $products->random(rand(1, 5));

            foreach ($productsToPurchase as $product) {
                // Pour un achat, le coût est généralement inférieur au prix de vente
                // Pour un achat, le prix d'achat est généralement inférieur au prix de vente du produit
                $purchasePrice = $product->price * (rand(60, 80) / 100); // Prix d'achat entre 60% et 80% du prix de vente
                $purchaseItems[] = [
                    'product_id' => $product->id,
                    'quantity' => rand(5, 20), // On achète en plus grande quantité
                    'unit_price' => round($purchasePrice, 2), // C'est le prix d'achat unitaire
                ];
            }

            // On utilise le service pour créer l'achat, ce qui garantit la mise à jour du stock
            $purchaseService->processPurchase($suppliers->random()->id, $purchaseItems);
        }
    }
}
