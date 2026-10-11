<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Étape 2 : migration des données existantes (insérées ici comme en production, sans les nouvelles colonnes).
 */
class MigrateVariantsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_DATE = '2026-01-15 10:00:00';

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create(['name' => 'tissu']);
        $colors = [];
        foreach (['Rouge', 'Bleu', 'No Variant', 'Noir'] as $name) {
            $colors[$name] = DB::table('colors')->insertGetId(['name' => $name, 'code' => $name, 'created_at' => self::OLD_DATE, 'updated_at' => self::OLD_DATE]);
        }

        $legacy = function (string $productName, array $colorNames) use ($category, $colors) {
            $productId = DB::table('products')->insertGetId([
                'name' => $productName, 'category_id' => $category->id, 'price' => 0, 'created_at' => self::OLD_DATE, 'updated_at' => self::OLD_DATE,
            ]);
            foreach ($colorNames as $colorName) {
                $this->ids[$productName][] = DB::table('product_colors')->insertGetId([
                    'product_id' => $productId, 'color_id' => $colors[$colorName], 'stock' => 4, 'alert_stock' => 5, 'price' => 1000,
                    'created_at' => self::OLD_DATE, 'updated_at' => self::OLD_DATE,
                ]);
            }

            return $productId;
        };

        $this->ids['products'] = [
            'Wax' => $legacy('Wax', ['Rouge', 'Bleu']),
            'Gel' => $legacy('Gel déshydratant', ['No Variant']),
            'Velours' => $legacy('Velours', ['Noir', 'Noir']), // doublon existant (aucune contrainte jusqu'ici)
        ];
    }

    private function migrate(bool $dryRun = false): string
    {
        Artisan::call('catalog:migrate-variants', $dryRun ? ['--dry-run' => true] : []);

        return Artisan::output();
    }

    /** @test */
    public function the_dry_run_reports_without_writing_anything()
    {
        $output = $this->migrate(dryRun: true);

        $this->assertStringContainsString('Simulation', $output);
        $this->assertMatchesRegularExpression('/Variantes à migrer\s*\|\s*5/', $output);
        $this->assertMatchesRegularExpression('/Doublons détectés\s*\|\s*1/', $output);
        $this->assertSame(5, DB::table('product_colors')->whereNull('option_values')->whereNull('sku')->count());
        $this->assertSame(3, DB::table('products')->whereNull('options')->count());
    }

    /** @test */
    public function the_migration_fills_options_signatures_and_skus_from_the_colors()
    {
        $output = $this->migrate();

        [$rouge, $bleu] = $this->ids['Wax'];
        $this->assertSame(['Couleur' => 'Rouge'], ProductColor::find($rouge)->option_values);
        $this->assertSame('WAX-ROUGE', ProductColor::find($rouge)->sku);
        $this->assertSame('couleur=bleu', ProductColor::find($bleu)->signature);
        $this->assertSame([['name' => 'Couleur', 'values' => ['Rouge', 'Bleu']]], Product::find($this->ids['products']['Wax'])->options);

        // « No Variant » devient une variante par défaut, sans option
        $gel = ProductColor::find($this->ids['Gel déshydratant'][0]);
        $this->assertSame([], $gel->option_values);
        $this->assertSame('GEL-DESHYDRATANT', $gel->sku);
        $this->assertSame([], Product::find($this->ids['products']['Gel'])->options);

        // Doublon : signalé, chaque variante reçoit un SKU distinct
        $this->assertSame(['VELOURS-NOIR', 'VELOURS-NOIR-2'], ProductColor::whereIn('id', $this->ids['Velours'])->orderBy('id')->pluck('sku')->all());
        $this->assertStringContainsString('Doublons', $output);
        $this->assertStringContainsString('Velours', $output);

        // Rien d'autre n'est touché : couleur conservée, dates de modification inchangées
        $this->assertSame(0, DB::table('product_colors')->whereNull('color_id')->count());
        $this->assertSame(5, DB::table('product_colors')->where('updated_at', self::OLD_DATE)->count());
        $this->assertSame(3, DB::table('products')->where('updated_at', self::OLD_DATE)->count());
    }

    /** @test */
    public function the_migration_can_be_run_again_safely()
    {
        $this->migrate();
        $before = DB::table('product_colors')->orderBy('id')->get(['id', 'option_values', 'signature', 'sku'])->toArray();

        $output = $this->migrate();

        $this->assertMatchesRegularExpression('/Variantes déjà migrées \(ignorées\)\s*\|\s*5/', $output);
        $this->assertMatchesRegularExpression('/Variantes migrées\s*\|\s*0/', $output);
        $this->assertEquals($before, DB::table('product_colors')->orderBy('id')->get(['id', 'option_values', 'signature', 'sku'])->toArray());
    }
}
