<?php

namespace Tests\Feature\Inventory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bases qui ont encore les tables de l'ancien essai src/Domain (ex. la base Laragon, peut-être la production).
 */
class LegacyDomainTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require base_path('app/Modules/Inventory/Database/Migrations/2026_10_04_105000_set_aside_legacy_domain_tables.php'))->up();
    }

    /** Remet la base dans l'état d'une base qui a connu l'ancien essai. */
    private function createLegacyTables(): void
    {
        Schema::drop('inventory_lines');
        Schema::drop('inventories');

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->integer('quantity_available')->default(0);
            $table->string('storage_location')->nullable();
            $table->integer('alert_threshold')->nullable();
            $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 10, 2);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
        DB::table('migrations')->insert([
            ['migration' => '2026_01_01_000001_create_product_variants_table', 'batch' => 1],
            ['migration' => '2026_01_01_000002_create_inventories_table', 'batch' => 1],
        ]);
    }

    /** @test */
    public function empty_legacy_tables_are_removed()
    {
        $this->createLegacyTables();

        $this->runMigration();

        $this->assertFalse(Schema::hasTable('inventories'), 'Le nom est libéré pour le module Inventaire');
        $this->assertFalse(Schema::hasTable('product_variants'));
        $this->assertSame(0, DB::table('migrations')->where('migration', 'like', '2026_01_01_%')->count());
    }

    /** @test */
    public function a_legacy_table_with_data_is_renamed_not_deleted()
    {
        $this->createLegacyTables();
        DB::table('inventories')->insert(['sku' => 'ABC', 'quantity_available' => 3]);

        $this->runMigration();

        $this->assertFalse(Schema::hasTable('inventories'));
        $this->assertSame('ABC', DB::table('legacy_inventories')->value('sku'));
        $this->assertFalse(Schema::hasTable('product_variants'), 'La table vide est supprimée');
    }

    /** @test */
    public function the_current_inventory_tables_are_left_untouched()
    {
        $this->runMigration();

        $this->assertTrue(Schema::hasColumn('inventories', 'status'));
        $this->assertTrue(Schema::hasTable('inventory_lines'));
    }
}
