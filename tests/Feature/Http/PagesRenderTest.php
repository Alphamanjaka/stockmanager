<?php

namespace Tests\Feature\Http;

use App\Core\Exports\ExportRegistry;
use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test de fumée : chaque page doit s'afficher (HTTP 200) avec des données réelles.
 * Il protège surtout contre les vues ou classes introuvables après un déplacement de code.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->backOffice()->create();
        $this->seller = User::factory()->frontOffice()->create();

        $category = Category::factory()->create(['name' => 'tissu']);
        $color = Color::factory()->create(['name' => 'Rouge', 'code' => '#ff0000']);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);
        $variant = ProductColor::forceCreate([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'stock' => 3,
            'alert_stock' => 5,
            'price' => 1000,
            'price_purchase' => 700,
        ]);
        $supplier = Supplier::factory()->create();

        $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id, 'state' => 'Draft']);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_color_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 700,
            'subtotal' => 1400,
        ]);

        $customer = Customer::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $this->seller->id, 'customer_id' => $customer->id, 'total_brut' => 1000, 'total_net' => 1000]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_color_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $movement = StockMovement::factory()->create(['product_color_id' => $variant->id]);

        $this->ids = compact('category', 'color', 'product', 'variant', 'supplier', 'customer', 'purchase', 'sale', 'movement');
        $this->ids = array_map(fn ($model) => $model->id, $this->ids);
    }

    /** @test */
    public function back_office_pages_render()
    {
        $i = $this->ids;
        $this->actingAs($this->admin);

        $this->assertPagesRender([
            '/admin/dashboard',
            '/admin/dashboard/chart-data',
            // Catalogue
            '/admin/products',
            '/admin/products/create',
            "/admin/products/{$i['product']}",
            "/admin/products?category={$i['category']}&search=ti&sort=price&order=desc",
            '/admin/products?status=archived',
            '/admin/categories',
            '/admin/categories?status=archived',
            '/admin/categories/create',
            "/admin/categories/{$i['category']}",
            "/admin/categories/{$i['category']}/edit",
            '/admin/colors',
            '/admin/colors/create',
            "/admin/colors/{$i['color']}/edit",
            '/admin/colors?status=archived',
            '/admin/brands',
            '/admin/brands?status=archived',
            '/admin/brands/create',
            // Stock
            '/admin/movements',
            '/admin/movements/create',
            '/admin/movements?type=in&date_from=2020-01-01&date_to=2099-12-31',
            "/admin/movements/{$i['movement']}",
            // Achats
            '/admin/purchases',
            '/admin/purchases/create',
            "/admin/purchases/{$i['purchase']}",
            "/admin/purchases/{$i['purchase']}/edit",
            '/admin/purchases/create-from-shortage',
            '/admin/purchases/get-purchases-api',
            "/admin/purchases/{$i['purchase']}/pdf",
            "/admin/purchases/{$i['purchase']}/pdf/preview",
            // Ventes
            '/admin/sales',
            '/admin/sales/create',
            "/admin/sales/{$i['sale']}",
            "/admin/sales/{$i['sale']}/pdf",
            "/admin/sales/{$i['sale']}/receipt",
            '/admin/sales?search=a&date_from=2020-01-01&date_to=2099-12-31',
            // Contacts
            '/admin/suppliers',
            '/admin/suppliers/create',
            "/admin/suppliers/{$i['supplier']}",
            "/admin/suppliers/{$i['supplier']}/edit",
            '/admin/suppliers?status=archived',
            '/admin/customers',
            '/admin/customers/create',
            "/admin/customers/{$i['customer']}",
            "/admin/customers/{$i['customer']}/edit",
            '/admin/customers?status=archived',
            "/admin/sales?customer={$i['customer']}",
            // Administration
            '/admin/settings',
            '/admin/imports',
            '/admin/imports/template/products',
            '/admin/users',
            '/admin/users/create',
            "/admin/users/{$this->seller->id}/edit",
            '/admin/users?status=archived',
            // Inventaires
            '/admin/inventories',
            '/admin/inventories/create',
        ]);
    }

    /** @test */
    public function every_list_exports_to_pdf_and_excel()
    {
        $this->actingAs($this->admin);

        $urls = [];
        foreach (array_keys(app(ExportRegistry::class)->all()) as $list) {
            foreach (['pdf', 'xlsx'] as $format) {
                $urls[] = "/admin/exports/{$list}/{$format}";
                $urls[] = "/admin/exports/{$list}/{$format}?status=archived&search=zz";
            }
        }

        $this->assertPagesRender($urls);
    }

    /** @test */
    public function back_office_ajax_endpoints_respond()
    {
        $i = $this->ids;
        $this->actingAs($this->admin);

        $this->assertPagesRender([
            "/api/products/{$i['product']}/variants",
            "/api/products/{$i['product']}/stock-evolution",
            "/api/products/{$i['product']}/movements",
            "/api/products/{$i['product']}/movements?variant_id={$i['variant']}",
            '/api/categories',
            "/api/categories/{$i['category']}",
            '/api/colors',
            '/api/global-search?q=ti',
        ]);
    }

    /** @test */
    public function front_office_pages_render()
    {
        $i = $this->ids;
        $this->actingAs($this->seller);

        $this->assertPagesRender([
            '/saler/dashboard',
            '/saler/products',
            '/saler',
            '/saler/create',
            "/saler/{$i['sale']}",
            "/saler/{$i['sale']}/pdf",
            "/saler/{$i['sale']}/receipt",
        ]);
    }

    /** @test */
    public function guest_pages_render()
    {
        $this->assertPagesRender(['/login']);
    }

    private function assertPagesRender(array $urls): void
    {
        $failures = [];

        foreach ($urls as $url) {
            $response = $this->get($url);

            if ($response->getStatusCode() !== 200) {
                $error = $response->exception
                    ? class_basename($response->exception).': '.$response->exception->getMessage()
                    : '';
                $failures[] = "{$url} → {$response->getStatusCode()} {$error}";
            }
        }

        $this->assertSame([], $failures, "Pages en erreur :\n".implode("\n", $failures));
    }
}
