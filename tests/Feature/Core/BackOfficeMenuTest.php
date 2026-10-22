<?php

namespace Tests\Feature\Core;

use App\Core\Navigation\BackOfficeMenu;
use App\Core\Navigation\MenuItem;
use App\Core\Navigation\Navigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Menu du back office (lanceur de modules) : déclaré par les modules, résolu pour la page courante.
 */
class BackOfficeMenuTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function each_shortcut_is_used_once_and_never_takes_the_launcher_key()
    {
        $this->assertSame([], app(BackOfficeMenu::class)->shortcutConflicts());
    }

    /** @test */
    public function menu_labels_are_translated_in_every_language()
    {
        $missing = [];

        foreach (app(BackOfficeMenu::class)->translationKeys() as $key) {
            foreach (array_keys(config('app.supported_locales')) as $locale) {
                if (! Lang::has($key, $locale, false)) {
                    $missing[] = "{$locale}: {$key}";
                }
            }
        }

        $this->assertSame([], $missing);
    }

    /** @test */
    public function entries_without_route_or_permission_are_hidden_and_empty_spaces_disappear()
    {
        Gate::define('nobody', fn (?User $user = null) => false);

        $menu = new BackOfficeMenu;
        $menu->addSpaces([
            'shop' => ['label' => 'backoffice.sales', 'icon' => 'bi-shop'],
            'ghost' => ['label' => 'backoffice.purchases', 'icon' => 'bi-question-circle'],
        ]);
        $menu->addEntries('shop', [
            ['label' => 'backoffice.sales', 'route' => 'admin.sales.index'],
            ['label' => 'backoffice.users', 'route' => 'admin.users.index', 'can' => 'nobody'],
            ['label' => 'backoffice.import', 'route' => 'admin.route-removed'],
            ['label' => 'backoffice.settings', 'children' => [
                ['label' => 'backoffice.settings', 'route' => 'admin.route-removed'],
            ]],
        ]);
        $menu->addEntries('ghost', [['label' => 'backoffice.purchases', 'route' => 'admin.route-removed']]);

        $navigation = $menu->resolve(Request::create('/'));

        $this->assertSame(['shop'], array_map(fn ($space) => $space->key, $navigation->spaces));
        $this->assertSame([__('backoffice.sales')], array_map(fn (MenuItem $item) => $item->label, $navigation->spaces[0]->items));
    }

    /** @test */
    public function breadcrumb_and_active_entries_follow_the_current_page()
    {
        $cases = [
            '/admin/dashboard' => ['dashboard', ['backoffice.dashboard']],
            '/admin/products' => ['catalog', ['backoffice.spaces.catalog', 'backoffice.products']],
            '/admin/products/create' => ['catalog', ['backoffice.spaces.catalog', 'backoffice.products', 'backoffice.menu.new_product']],
            '/admin/products/12' => ['catalog', ['backoffice.spaces.catalog', 'backoffice.products', 'backoffice.crumbs.show']],
            '/admin/categories/3/edit' => ['catalog', ['backoffice.spaces.catalog', 'backoffice.categories', 'backoffice.crumbs.edit']],
            // Espace et entrée de même nom : « Ventes » n'est pas répété
            '/admin/sales/create' => ['sale', ['backoffice.sales', 'backoffice.menu.new_sale']],
            '/admin/purchases/5/edit' => ['purchase', ['backoffice.purchases', 'backoffice.crumbs.edit']],
            '/admin/purchases/create-from-shortage' => ['purchase', ['backoffice.purchases', 'backoffice.menu.restock']],
            // Pages d'un module rangées dans l'espace d'un autre
            '/admin/suppliers/7' => ['purchase', ['backoffice.purchases', 'backoffice.suppliers', 'backoffice.crumbs.show']],
            '/admin/inventories/create' => ['stock', ['backoffice.spaces.stock', 'backoffice.stock_counts', 'backoffice.menu.new_stock_count']],
            '/admin/users/2/edit' => ['admin', ['backoffice.administration', 'backoffice.users', 'backoffice.crumbs.edit']],
        ];

        foreach ($cases as $uri => [$space, $crumbs]) {
            $navigation = $this->navigationFor($uri);

            $this->assertSame($space, $navigation->current?->key, $uri);
            $this->assertSame(array_map(fn ($key) => __($key), $crumbs), array_column($navigation->breadcrumb, 'label'), $uri);
            $this->assertNull($navigation->breadcrumb[array_key_last($navigation->breadcrumb)]['url'], "{$uri} : la page courante n'a pas de lien");
        }
    }

    /** @test */
    public function the_most_precise_link_wins_in_a_submenu()
    {
        $products = $this->navigationFor('/admin/products/create')->current->items[0];

        $this->assertTrue($products->active);
        $this->assertSame(
            [__('backoffice.menu.new_product')],
            array_values(array_map(fn (MenuItem $child) => $child->label, array_filter($products->children, fn (MenuItem $child) => $child->active))),
        );
    }

    /** @test */
    public function back_office_pages_show_the_launcher_and_the_module_menu()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->get('/admin/products/create')
            ->assertOk()
            ->assertSee('id="bo-launcher-toggle"', false)
            ->assertSee('class="bo-module-nav"', false)
            ->assertSee('id="bo-shortcuts"', false)
            ->assertSeeInOrder([__('backoffice.spaces.catalog'), __('backoffice.spaces.stock'), __('backoffice.administration')]);

        // Le tableau de bord est un espace d'une seule page : pas de menu de module
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('class="bo-module-nav"', false);
    }

    private function navigationFor(string $uri): Navigation
    {
        $request = Request::create($uri);
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        return app(BackOfficeMenu::class)->resolve($request);
    }
}
