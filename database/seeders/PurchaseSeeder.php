<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\PurchaseService;
use App\Services\ProductService;
use App\Services\SupplierService;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $productService = app(ProductService::class);
        $products = $productService->getAllProducts();

        // --- SEEDING DES ACHATS (PURCHASES) ---
        $purchaseService = app(PurchaseService::class);
        $supplierServivce = app(SupplierService::class);
        $suppliers = $supplierServivce->getAllSuppliers();

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
