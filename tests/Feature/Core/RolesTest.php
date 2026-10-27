<?php

namespace Tests\Feature\Core;

use App\Core\Support\Role;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rôles du back office : administrateur (tout), gérant (sans utilisateurs, paramètres ni import),
 * magasinier (réceptions, inventaires, mouvements, produits en lecture). Voir App\Core\Support\Role.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        ProductColor::forceCreate(['product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 3, 'price' => 1000]);

        return $product;
    }

    /** @test */
    public function every_back_office_route_has_a_name_so_that_roles_can_be_checked()
    {
        $unnamed = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('ensure.back.office', $route->gatherMiddleware(), true) && ! $route->getName())
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unnamed, 'Une route sans nom est refusée à tous sauf aux administrateurs');
    }

    /** @test */
    public function the_administrator_opens_everything()
    {
        foreach (['admin.dashboard', 'admin.users.index', 'admin.settings.index', 'admin.sales.index', 'admin.imports.index'] as $route) {
            $this->assertTrue(Role::allows(Role::ADMIN, $route), $route);
        }
    }

    /** @test */
    public function the_manager_follows_the_activity_without_users_settings_or_import()
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.sales.index'))->assertOk();
        $this->get(route('admin.purchases.index'))->assertOk();
        $this->get(route('admin.daily-summary.index'))->assertOk();
        $this->get(route('admin.exports.download', ['list' => 'sales', 'format' => 'xlsx']))->assertOk();

        foreach (['admin.users.index', 'admin.settings.index', 'admin.imports.index', 'admin.users.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('dashboard'));
        }
        $this->get(route('admin.exports.download', ['list' => 'users', 'format' => 'xlsx']))->assertRedirect(route('dashboard'));
        $this->put(route('admin.daily-summary.settings'), ['hour' => 20])->assertRedirect(route('dashboard'));
        $this->post(route('admin.users.store'), [
            'name' => 'Intrus', 'email' => 'intrus@boutique.mg', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => Role::ADMIN,
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('users', ['email' => 'intrus@boutique.mg']);

        // Menu : ni utilisateurs, ni paramètres
        $this->get(route('admin.dashboard'))
            ->assertSee(route('admin.sales.index'), false)
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.settings.index'), false);
    }

    /** @test */
    public function the_storekeeper_receives_counts_and_moves_stock_only()
    {
        $product = $this->product();
        $this->actingAs(User::factory()->storekeeper()->create());

        foreach (['admin.purchases.index', 'admin.inventories.index', 'admin.inventories.create', 'admin.movements.index',
            'admin.movements.create', 'admin.products.index', 'admin.labels.create'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee(route('admin.labels.create', ['product' => $product->id]), false)
            // Lecture seule : ni modification, ni archivage
            ->assertDontSee('id="editProductModal"', false)
            ->assertDontSee(route('admin.products.archive'), false);
        $this->getJson(route('api.products.variants', $product->id))->assertOk();
        $this->get(route('admin.exports.download', ['list' => 'movements', 'format' => 'xlsx']))->assertOk();

        // Ventes, commandes, tableau de bord, clients, paramètres : refusés
        foreach (['admin.dashboard', 'admin.sales.index', 'admin.purchases.create', 'admin.purchases.createFromShortage',
            'admin.customers.index', 'admin.suppliers.index', 'admin.settings.index', 'admin.users.index', 'admin.products.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('dashboard'));
        }
        $this->patch(route('admin.products.updateDetails', $product->id), ['name' => 'x'])->assertRedirect(route('dashboard'));
        $this->get(route('admin.exports.download', ['list' => 'sales', 'format' => 'xlsx']))->assertRedirect(route('dashboard'));
        $this->getJson(route('global.search', ['query' => 'wax']))->assertForbidden();

        // Une commande en attente : il enregistre la livraison, sans changer le statut ni modifier la commande
        $purchase = Purchase::factory()->create(['state' => Purchase::ORDERED]);
        $this->get(route('admin.purchases.show', $purchase))
            ->assertOk()
            ->assertSee(route('admin.purchases.receive', $purchase->id), false)
            ->assertDontSee(route('admin.purchases.updateState', $purchase->id), false)
            ->assertDontSee(route('admin.purchases.edit', $purchase->id), false);
        $this->get(route('admin.purchases.receive', $purchase->id))->assertOk();
        $this->patch(route('admin.purchases.updateState', $purchase->id), ['state' => 'Draft'])->assertRedirect(route('dashboard'));

        // Sa première page : les achats à réceptionner ; ni recherche globale ni lien vers les ventes
        $this->get(route('dashboard'))->assertRedirect(route('admin.purchases.index'));
        $this->get(route('admin.purchases.index'))
            ->assertDontSee('id="global-search-input"', false)
            ->assertDontSee(route('admin.sales.index'), false)
            ->assertDontSee(route('admin.purchases.create'), false)
            ->assertSee(route('admin.inventories.index'), false);
    }

    /** @test */
    public function each_role_lands_on_its_own_page_after_login()
    {
        foreach ([Role::ADMIN => 'admin.dashboard', Role::MANAGER => 'admin.dashboard', Role::STOREKEEPER => 'admin.purchases.index', Role::SELLER => 'saler.dashboard'] as $role => $landing) {
            $user = User::factory()->create(['role' => $role, 'email' => "{$role}@boutique.mg"]);

            $this->post(route('auth.login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route($landing));
            auth()->logout();
        }
    }

    /** @test */
    public function sellers_stay_out_of_the_back_office()
    {
        $this->actingAs(User::factory()->frontOffice()->create());

        $this->get(route('admin.purchases.index'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('saler.dashboard'));
    }

    /** @test */
    public function the_administrator_gives_the_new_roles()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->get(route('admin.users.create'))->assertOk()->assertSee(__('backoffice.roles.storekeeper'))->assertSee(__('backoffice.role_hints.manager'));

        $this->post(route('admin.users.store'), [
            'name' => 'Rabe', 'email' => 'rabe@boutique.mg', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => Role::STOREKEEPER,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'rabe@boutique.mg', 'role' => Role::STOREKEEPER]);

        $this->get(route('admin.users.index', ['role' => Role::STOREKEEPER]))->assertOk()->assertSee('rabe@boutique.mg');
        $this->post(route('admin.users.store'), [
            'name' => 'X', 'email' => 'x@boutique.mg', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'owner',
        ])->assertSessionHasErrors('role');
    }
}
