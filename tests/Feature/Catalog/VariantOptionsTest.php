<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Support\VariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_signature_ignores_order_and_case_and_includes_the_brand()
    {
        $a = VariantOptions::signature(['Couleur' => 'Noir', 'Taille' => '30 cm']);
        $b = VariantOptions::signature(['taille' => '30 CM ', 'COULEUR' => 'noir']);

        $this->assertSame('couleur=noir;taille=30 cm', $a);
        $this->assertSame($a, $b);
        $this->assertSame('couleur=noir;taille=30 cm|brand=7', VariantOptions::signature(['Couleur' => 'Noir', 'Taille' => '30 cm'], 7));
        $this->assertSame('', VariantOptions::signature([]));
    }

    /** @test */
    public function legacy_colors_become_a_couleur_option_except_technical_ones()
    {
        $this->assertSame(['Couleur' => 'Rouge'], VariantOptions::fromColor(new Color(['name' => ' Rouge '])));
        $this->assertSame([], VariantOptions::fromColor(new Color(['name' => 'No Variant'])));
        $this->assertSame([], VariantOptions::fromColor(new Color(['name' => 'Sans variante'])));
        $this->assertSame([], VariantOptions::fromColor(null));
    }

    /** @test */
    public function skus_are_readable_and_unique()
    {
        $this->assertSame('GEL-DESHYDRATANT-25ML', VariantOptions::generateSku('Gel déshydratant', ['Contenance' => '25 ml']));
        $this->assertSame('POSTICHE-LISSE-NOIR-30CM-BELLA', VariantOptions::generateSku('Postiche lisse', ['Couleur' => 'Noir', 'Taille' => '30 cm'], 'Bella'));
        $this->assertSame('GEL-DESHYDRATANT', VariantOptions::generateSku('Gel déshydratant', []));

        // Déjà pris en base, puis réservé pendant une même exécution : suffixes -2, -3
        $product = Product::factory()->create(['name' => 'Wax', 'category_id' => Category::factory()->create()->id]);
        ProductColor::forceCreate(['product_id' => $product->id, 'color_id' => Color::factory()->create(['name' => 'Rouge'])->id, 'price' => 1, 'stock' => 0]);

        $this->assertSame('WAX-ROUGE-2', VariantOptions::generateSku('Wax', ['Couleur' => 'Rouge']));
        $this->assertSame('WAX-ROUGE-3', VariantOptions::generateSku('Wax', ['Couleur' => 'Rouge'], reserved: ['WAX-ROUGE-2']));
        $this->assertLessThanOrEqual(64, strlen(VariantOptions::generateSku(str_repeat('Très long nom ', 20), ['A' => str_repeat('x', 50), 'B' => str_repeat('y', 50)])));
    }

    /** @test */
    public function labels_and_product_options_are_built_from_the_combinations()
    {
        $this->assertSame('Postiche lisse — Noir / 30 cm — Bella', VariantOptions::label('Postiche lisse', ['Couleur' => 'Noir', 'Taille' => '30 cm'], 'Bella'));
        $this->assertSame('Gel déshydratant', VariantOptions::label('Gel déshydratant', []));

        $this->assertSame(
            [['name' => 'Couleur', 'values' => ['Noir', 'Brun']], ['name' => 'Taille', 'values' => ['30 cm', '40 cm']]],
            VariantOptions::optionsFromVariants([
                ['Couleur' => 'Noir', 'Taille' => '30 cm'],
                ['Couleur' => 'Brun', 'Taille' => '30 cm'],
                ['Couleur' => 'Noir', 'Taille' => '40 cm'],
                [],
            ])
        );
    }
}
