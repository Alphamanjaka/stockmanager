<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Sale\Support\PosCatalog;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Archiver plutôt que supprimer : un élément archivé disparaît des listes et des listes de choix,
 * son historique reste complet, et seul un élément jamais utilisé peut être supprimé définitivement.
 */
class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create();
        $this->actingAs($this->admin);
    }

    private function variant(array $attributes = []): ProductColor
    {
        $product = Product::factory()->create(['name' => 'perruque lisse', 'category_id' => Category::factory()->create()->id]);

        return ProductColor::forceCreate($attributes + [
            'product_id' => $product->id,
            'color_id' => Color::factory()->create()->id,
            'stock' => 5,
            'alert_stock' => 2,
            'price' => 1000,
        ]);
    }

    /** @test */
    public function reference_data_can_be_archived_and_restored_from_its_list()
    {
        $records = [
            'brands' => Brand::factory()->create(),
            'colors' => Color::factory()->create(),
            'categories' => Category::factory()->create(),
            'suppliers' => Supplier::factory()->create(),
            'customers' => Customer::factory()->create(),
            'users' => User::factory()->frontOffice()->create(),
            'products' => $this->variant()->product,
        ];

        foreach ($records as $resource => $record) {
            $this->from(route("admin.{$resource}.index"))
                ->post(route("admin.{$resource}.archive"), ['ids' => [$record->id]])
                ->assertRedirect(route("admin.{$resource}.index"))
                ->assertSessionHas('success', '1 élément(s) archivé(s).');
            $this->assertTrue($record->fresh()->isArchived(), "{$resource} archivé");

            $this->post(route("admin.{$resource}.restore"), ['ids' => [$record->id]])->assertSessionHas('success');
            $this->assertFalse($record->fresh()->isArchived(), "{$resource} restauré");
        }
    }

    /** @test */
    public function archived_items_leave_the_list_and_appear_in_the_archives()
    {
        Supplier::factory()->create(['name' => 'Tissus Actifs']);
        Supplier::factory()->create(['name' => 'Tissus Anciens'])->archive();

        $this->get(route('admin.suppliers.index'))->assertOk()->assertSee('Tissus Actifs')->assertDontSee('Tissus Anciens');
        $this->get(route('admin.suppliers.index', ['status' => 'archived']))
            ->assertOk()->assertSee('Tissus Anciens')->assertDontSee('Tissus Actifs');

        $active = $this->variant();
        $archived = $this->variant();
        $archived->product->update(['name' => 'perruque frisée']);
        $archived->product->archive();

        $this->get(route('admin.products.index'))->assertSee('perruque lisse')->assertDontSee('perruque frisée');
        $this->get(route('admin.products.index', ['status' => 'archived']))->assertSee('perruque frisée');
        $this->assertModelExists($active);
    }

    /** @test */
    public function a_selection_is_archived_at_once_and_refusals_are_explained()
    {
        $empty = Category::factory()->create(['name' => 'vide']);
        $full = $this->variant()->product->category;

        $this->post(route('admin.categories.archive'), ['ids' => [$empty->id, $full->id]])
            ->assertSessionHas('success', '1 élément(s) archivé(s).')
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '1 produit(s) actif(s)'));

        $this->assertTrue($empty->fresh()->isArchived());
        $this->assertFalse($full->fresh()->isArchived());
    }

    /** @test */
    public function an_empty_selection_is_refused_with_a_message()
    {
        $this->post(route('admin.brands.archive'), ['ids' => []])->assertSessionHas('error', 'Aucun élément sélectionné.');
        $this->post(route('admin.brands.archive'), ['ids' => ['abc']])->assertSessionHas('error', 'Aucun élément sélectionné.');
    }

    /** @test */
    public function permanent_deletion_only_concerns_archived_items_never_used()
    {
        $unused = Brand::factory()->create();

        // Actif : il faut d'abord l'archiver
        $this->delete(route('admin.brands.destroy', $unused->id))->assertSessionHas('error');
        $this->assertModelExists($unused);

        $unused->archive();
        $this->delete(route('admin.brands.destroy', $unused->id))
            ->assertRedirect(route('admin.brands.index', ['status' => 'archived']))
            ->assertSessionHas('success');
        $this->assertModelMissing($unused);
    }

    /** @test */
    public function an_archived_item_with_history_stays_archived()
    {
        $variant = $this->variant();
        StockMovement::factory()->create(['product_color_id' => $variant->id]);
        $product = $variant->product;
        $product->archive();

        $supplier = Supplier::factory()->create();
        Purchase::factory()->create(['supplier_id' => $supplier->id]);
        $supplier->archive();

        $customer = Customer::factory()->create();
        Sale::factory()->create(['customer_id' => $customer->id]);
        $customer->archive();

        $category = $product->category;
        $category->archive();

        foreach ([
            route('admin.products.destroy', $product->id) => $product,
            route('admin.suppliers.destroy', $supplier->id) => $supplier,
            route('admin.customers.destroy', $customer->id) => $customer,
            route('admin.categories.destroy', $category->id) => $category,
        ] as $url => $record) {
            $this->delete($url)->assertSessionHas('error', fn (string $message) => str_contains($message, 'déjà utilisé'));
            $this->assertModelExists($record);
        }

        $this->assertModelExists($variant);
        $this->assertSame(1, StockMovement::where('product_color_id', $variant->id)->count());
    }

    /** @test */
    public function the_database_refuses_deletions_that_would_erase_history()
    {
        $variant = $this->variant();
        StockMovement::factory()->create(['product_color_id' => $variant->id]);

        // Avant : la cascade effaçait les produits de la catégorie, puis leurs mouvements
        $this->expectException(QueryException::class);
        $variant->product->category->delete();
    }

    /** @test */
    public function a_category_with_active_products_or_subcategories_cannot_be_archived()
    {
        $category = $this->variant()->product->category;
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertStringContainsString('1 produit(s) actif(s)', $category->archiveBlocker());
        $this->assertStringContainsString('1 sous-catégorie(s) active(s)', $parent->archiveBlocker());

        $category->products()->first()->archive();
        $this->assertNull($category->archiveBlocker());
    }

    /** @test */
    public function archived_items_are_no_longer_offered_in_forms()
    {
        $kept = $this->variant();
        $archived = $this->variant();
        $archived->product->archive();
        $oldBrand = Brand::factory()->create(['name' => 'Ancienne marque']);
        $oldBrand->archive();
        $oldSupplier = Supplier::factory()->create(['name' => 'Ancien fournisseur']);
        $oldSupplier->archive();
        $oldCustomer = Customer::factory()->create(['name' => 'Ancien client']);
        $oldCustomer->archive();

        $pos = app(PosCatalog::class)->build();
        $this->assertSame([$kept->id], $pos['items']->pluck('id')->all());
        $this->assertNotContains('Ancienne marque', collect($pos['brands'])->pluck('name')->all());

        $this->get(route('admin.purchases.create'))->assertOk()->assertDontSee('Ancien fournisseur');
        $this->get(route('admin.sales.create'))->assertOk()->assertDontSee('Ancien client');
        $this->get(route('admin.products.create'))->assertOk()->assertDontSee('Ancienne marque');
    }

    /** @test */
    public function an_archived_product_cannot_be_sold()
    {
        $variant = $this->variant();
        $variant->product->archive();

        $this->expectExceptionMessage('Produit archivé');
        app(SaleService::class)->createSale([['product_color_id' => $variant->id, 'quantity' => 1]]);
    }

    /** @test */
    public function an_archived_product_is_not_reported_in_stock_alerts()
    {
        $variant = $this->variant(['stock' => 0, 'alert_stock' => 5]);

        $this->get(route('admin.purchases.createFromShortage'))->assertOk()->assertSee('perruque lisse');

        $variant->product->archive();
        $this->get(route('admin.purchases.createFromShortage'))->assertOk()->assertDontSee('perruque lisse');
    }

    /** @test */
    public function an_admin_cannot_archive_their_own_account()
    {
        $this->post(route('admin.users.archive'), ['ids' => [$this->admin->id]])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'votre propre compte'));

        $this->assertFalse($this->admin->fresh()->isArchived());
    }

    /** @test */
    public function an_archived_account_can_no_longer_sign_in_and_is_signed_out()
    {
        $seller = User::factory()->frontOffice()->create(['email' => 'vendeur@example.com', 'password' => 'secret-123']);
        $seller->archive();
        auth()->logout();

        $this->post(route('auth.login'), ['email' => 'vendeur@example.com', 'password' => 'secret-123', 'role' => 'front_office'])
            ->assertSessionHasErrors(['email' => 'Votre compte a été désactivé : contactez un administrateur.']);
        $this->assertGuest();

        // Archivé pendant sa session : déconnecté à la requête suivante
        $seller->unarchive();
        $this->actingAs($seller);
        $seller->archive();
        $this->get('/saler/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** @test */
    public function the_category_api_follows_the_same_rule()
    {
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertStatus(422);
        $this->assertModelExists($category);

        $category->archive();
        $this->deleteJson("/api/categories/{$category->id}")->assertOk();
        $this->assertModelMissing($category);
    }
}
