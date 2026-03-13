<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur pour l'authentification si nécessaire (selon middleware)
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_update_a_draft_purchase()
    {
        // 1. Préparer les données
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create(); // Nouveau fournisseur

        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 200]);

        // Créer une commande en Draft
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier1->id,
            'state' => 'Draft',
            'total_amount' => 1000,
        ]);

        // Ligne existante
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        // 2. Données de mise à jour (Changement fournisseur + Changement produit + Ajout produit)
        $updateData = [
            'supplier_id' => $supplier2->id,
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 5, // Quantité modifiée (10 -> 5)
                    'unit_price' => 110, // Prix modifié
                ],
                [
                    'product_id' => $product2->id, // Nouveau produit
                    'quantity' => 2,
                    'unit_price' => 150,
                ]
            ]
        ];

        // 3. Action
        $response = $this->put(route('admin.purchases.update', $purchase->id), $updateData);

        // 4. Assertions
        $response->assertRedirect(route('admin.purchases.show', $purchase->id));
        $response->assertSessionHas('success');

        // Vérifier la mise à jour de l'entête
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'supplier_id' => $supplier2->id,
            'total_amount' => (5 * 110) + (2 * 150), // 550 + 300 = 850
        ]);

        // Vérifier les lignes (les anciennes doivent être supprimées/remplacées)
        $this->assertCount(2, $purchase->fresh()->items);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'unit_price' => 110,
        ]);
    }

    /** @test */
    public function it_cannot_update_a_received_purchase()
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'state' => 'Received', // Déjà reçu
        ]);

        $updateData = [
            'supplier_id' => $supplier->id,
            'products' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]]
        ];

        $response = $this->put(route('admin.purchases.update', $purchase->id), $updateData);

        $response->assertSessionHas('error'); // Doit retourner une erreur
    }
}
