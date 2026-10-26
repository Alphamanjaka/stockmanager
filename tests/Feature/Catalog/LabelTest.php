<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Support\LabelFormat;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Étiquettes code-barres : préparation (sélection depuis un produit ou un achat), puis planche PDF.
 */
class LabelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->backOffice()->create();
        $this->category = Category::factory()->create();
    }

    private function variant(string $name, int $stock = 0): ProductColor
    {
        $product = Product::factory()->create(['name' => $name, 'category_id' => $this->category->id]);

        return ProductColor::forceCreate([
            'product_id' => $product->id, 'color_id' => Color::factory()->create()->id, 'stock' => $stock, 'price' => 2500,
        ]);
    }

    /** @test */
    public function the_page_preselects_the_variants_of_a_product_with_one_label_per_item_in_stock()
    {
        $gel = $this->variant('Gel', stock: 3);
        $peigne = $this->variant('Peigne');

        $this->actingAs($this->admin)->get(route('admin.labels.create', ['product' => $gel->product_id]))
            ->assertOk()
            ->assertSee('name="items['.$gel->id.']" value="3"', false)
            ->assertDontSee('name="items['.$peigne->id.']"', false);

        // Sélection passée par l'adresse (quantités reçues d'un achat)
        $this->actingAs($this->admin)->get(route('admin.labels.create', ['variants' => [$peigne->id => 5]]))
            ->assertOk()
            ->assertSee('name="items['.$peigne->id.']" value="5"', false);
    }

    /** @test */
    public function the_labels_are_printed_as_a_pdf_in_every_format()
    {
        $gel = $this->variant('Gel');

        foreach (LabelFormat::keys() as $format) {
            $response = $this->actingAs($this->admin)->post(route('admin.labels.print'), [
                'items' => [$gel->id => 30], 'format' => $format, 'skip' => 2, 'price' => 1, 'company' => 1,
            ]);

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'), $format);
        }
    }

    /** @test */
    public function each_label_has_its_place_on_the_sheet()
    {
        $format = LabelFormat::get('a4-3x7');

        $this->assertSame(['top' => 15.15, 'left' => 7.25], LabelFormat::position($format, 0));
        // 5e case : 2e ligne, 2e colonne (63,5 mm + 2,5 mm d'écart ; 38,1 mm de haut)
        $this->assertSame(['top' => 53.25, 'left' => 73.25], LabelFormat::position($format, 4));
        $this->assertSame(21, $format['per_page']);
        $this->assertSame(LabelFormat::DEFAULT, LabelFormat::get('inconnu')['key']);
    }

    /** @test */
    public function nothing_to_print_or_too_many_labels_is_refused()
    {
        $gel = $this->variant('Gel');

        $this->actingAs($this->admin)->from(route('admin.labels.create'))
            ->post(route('admin.labels.print'), ['items' => [$gel->id => 0]])
            ->assertRedirect(route('admin.labels.create'))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)->from(route('admin.labels.create'))
            ->post(route('admin.labels.print'), ['items' => [$gel->id => 5000]])
            ->assertSessionHasErrors('items.'.$gel->id);
    }

    /** @test */
    public function the_purchase_page_links_to_the_labels_of_the_received_items()
    {
        $gel = $this->variant('Gel');
        $purchase = Purchase::factory()->create(['state' => 'PartiallyReceived']);
        $purchase->items()->create(['product_color_id' => $gel->id, 'quantity' => 6, 'received_quantity' => 4, 'unit_price' => 1000, 'subtotal' => 6000]);

        $this->actingAs($this->admin)->get(route('admin.purchases.show', $purchase->id))
            ->assertOk()
            ->assertSee(e(route('admin.labels.create', ['variants' => [$gel->id => 4]])), false);
    }

    /** @test */
    public function sellers_cannot_print_labels()
    {
        $this->actingAs(User::factory()->frontOffice()->create())
            ->get(route('admin.labels.create'))
            ->assertRedirect(route('dashboard'));
    }
}
