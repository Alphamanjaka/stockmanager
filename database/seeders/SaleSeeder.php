<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\SaleService;
use App\Services\ProductService;
class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $productService = app(ProductService::class);
        $products = $productService->getAllProducts();

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
                $quantity = rand(1, min(2, $product->quantity_stock));
                if ($quantity <= 0) continue;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ];
            }

            if (empty($saleItems)) continue;

            $discount = rand(0, 1) ? rand(5, 50) : 0; // Une chance sur deux d'avoir une remise
            $saleService->createSale($saleItems, $discount, 1); // On associe toutes les ventes à l'utilisateur admin (id=1)
        }
    }
}
