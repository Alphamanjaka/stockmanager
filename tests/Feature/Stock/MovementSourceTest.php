<?php

namespace Tests\Feature\Stock;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chaque mouvement de stock garde son auteur et le document qui l'a provoqué,
 * au lieu de les deviner dans le texte du motif.
 */
class MovementSourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductColor $wax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create(['name' => 'Rova']);

        $product = Product::factory()->create(['name' => 'Wax', 'category_id' => Category::factory()->create()->id]);
        $this->wax = ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => 20, 'price' => 1000,
        ]);
    }

    private function lastMovement(): StockMovement
    {
        return StockMovement::latest('id')->firstOrFail();
    }

    /** @test */
    public function every_operation_records_its_document_and_its_author()
    {
        $seller = User::factory()->frontOffice()->create();
        $this->actingAs($seller);
        $sale = app(SaleService::class)->createSale([['product_color_id' => $this->wax->id, 'quantity' => 2]]);
        $this->assertMovement(MovementSource::SALE, $sale->id, $seller, 'Sale');

        $this->actingAs($this->admin);
        $purchases = app(PurchaseService::class);
        $purchase = $purchases->processPurchase(null, [['product_color_id' => $this->wax->id, 'quantity' => 5, 'unit_price' => 600]]);
        $purchases->changeState($purchase, 'Received');
        $this->assertMovement(MovementSource::PURCHASE, $purchase->id, $this->admin, 'Purchase');

        $inventories = app(InventoryService::class);
        $inventory = $inventories->create(null, null, $this->admin->id);
        $inventories->saveCounts($inventory, [$inventory->lines()->value('id') => 20]);
        $inventories->validate($inventory, $this->admin->id);
        $this->assertMovement(MovementSource::INVENTORY, $inventory->id, $this->admin, 'Stock count');

        app(StockService::class)->correct($this->wax->id, 'out', 1, 'casse');
        $this->assertMovement(MovementSource::CORRECTION, null, $this->admin, 'Correction');

        // Réception annulée : l'achat est supprimé, la source ne cite plus de document
        $purchases->deletePurchase($purchase->id);
        $this->assertMovement(MovementSource::PURCHASE, null, $this->admin, 'Purchase cancelled');
    }

    private function assertMovement(string $type, ?int $id, User $user, string $kind): void
    {
        $movement = $this->lastMovement();

        $this->assertSame($type, $movement->source_type);
        $this->assertSame($id, $movement->source_id);
        $this->assertSame($user->id, $movement->user_id);
        $this->assertSame($kind, $movement->kind()[1]);
    }

    /** @test */
    public function the_kind_comes_from_the_source_not_from_the_reason()
    {
        $stock = app(StockService::class);

        // Motif trompeur : la source fait foi
        $stock->removeStock($this->wax->id, 1, 'Correction de la vente', MovementSource::sale(1));
        $this->assertSame('Sale', $this->lastMovement()->kind()[1]);

        // Sans source (mouvement ancien non reconnu) : lecture du motif, comme avant
        $stock->removeStock($this->wax->id, 1, 'Vente au marché');
        $this->assertSame('Sale', $this->lastMovement()->kind()[1]);
        $stock->addStock($this->wax->id, 1, 'Retour');
        $this->assertSame('Stock in', $this->lastMovement()->kind()[1]);
    }

    /** @test */
    public function a_scheduled_task_has_no_author()
    {
        app(StockService::class)->addStock($this->wax->id, 1, 'Import', source: MovementSource::purchase(1));

        $this->assertNull($this->lastMovement()->user_id);
    }

    /** @test */
    public function the_movement_pages_show_the_author_and_link_the_document()
    {
        $this->actingAs($this->admin);
        $inventories = app(InventoryService::class);
        $inventory = $inventories->create(null, null, $this->admin->id);
        $inventories->saveCounts($inventory, [$inventory->lines()->value('id') => 18]);
        $inventories->validate($inventory, $this->admin->id);
        $movement = $this->lastMovement();

        $this->get(route('admin.movements.index'))->assertOk()->assertSee('Rova');
        $this->get(route('admin.movements.show', $movement->id))
            ->assertOk()
            ->assertSee('Rova')
            ->assertSee(route('admin.inventories.show', $inventory->id), false);
    }

    /** @test */
    public function the_migration_finds_the_source_of_past_movements_in_their_reason()
    {
        $this->actingAs($this->admin);
        $sale = app(SaleService::class)->createSale([['product_color_id' => $this->wax->id, 'quantity' => 1]]);
        $purchases = app(PurchaseService::class);
        $purchase = $purchases->processPurchase(null, [['product_color_id' => $this->wax->id, 'quantity' => 1, 'unit_price' => 600]]);
        $purchases->changeState($purchase, 'Received');
        $inventory = Inventory::create(['reference' => 'INV-20261001-ABCD', 'status' => Inventory::VALIDATED]);

        // Base d'avant la migration : pas de colonnes d'auteur ni de source
        $migration = require base_path('app/Modules/Stock/Database/Migrations/2026_10_23_100000_add_user_and_source_to_stock_movements.php');
        $migration->down();
        DB::table('stock_movements')->delete();
        foreach ([
            "Vente {$sale->reference}", "Réception Achat #{$purchase->reference}", 'Inventaire INV-20261001-ABCD',
            'Annulation Réception Achat #PUR-OLD', 'Correction : casse', 'Ajustement manuel',
        ] as $reason) {
            DB::table('stock_movements')->insert([
                'product_color_id' => $this->wax->id, 'quantity' => -1, 'type' => 'out', 'reason' => $reason,
                'stock_before' => 1, 'stock_after' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $migration->up();

        $sources = DB::table('stock_movements')->orderBy('id')->get(['source_type', 'source_id'])
            ->map(fn ($row) => [$row->source_type, $row->source_id])->all();
        $this->assertEquals([
            ['sale', $sale->id],
            ['purchase', $purchase->id],
            ['inventory', $inventory->id],
            ['purchase', null],
            ['correction', null],
            [null, null],
        ], $sources);
    }
}
