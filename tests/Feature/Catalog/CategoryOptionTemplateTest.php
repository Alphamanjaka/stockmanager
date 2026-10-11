<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Support\VariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryOptionTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function form_options_are_cleaned()
    {
        $this->assertSame(
            [['name' => 'Couleur', 'values' => ['Noir', 'Brun']], ['name' => 'Taille', 'values' => []]],
            VariantOptions::normalizeOptions([
                ['name' => ' Couleur ', 'values' => 'Noir, Brun, noir, '],
                ['name' => '', 'values' => 'ignoré'],
                ['name' => 'couleur', 'values' => 'doublon de nom'],
                ['name' => 'Taille', 'values' => ''],
            ])
        );
    }

    /** @test */
    public function a_category_stores_its_option_template()
    {
        $this->post(route('admin.categories.store'), [
            'name' => 'Postiches',
            'option_template' => [
                ['name' => 'Couleur', 'values' => 'Noir, Brun'],
                ['name' => 'Taille', 'values' => '30 cm, 40 cm'],
            ],
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'postiches')->firstOrFail();
        $this->assertSame([
            ['name' => 'Couleur', 'values' => ['Noir', 'Brun']],
            ['name' => 'Taille', 'values' => ['30 cm', '40 cm']],
        ], $category->option_template);

        $this->get(route('admin.categories.edit', $category->id))
            ->assertOk()
            ->assertSee('value="Taille"', false)
            ->assertSee('30 cm, 40 cm');
    }

    /** @test */
    public function the_template_can_be_changed_or_removed()
    {
        $category = Category::factory()->create(['name' => 'soins', 'option_template' => [['name' => 'Contenance', 'values' => ['25 ml']]]]);

        $this->put(route('admin.categories.update', $category->id), [
            'name' => 'soins',
            'option_template' => [['name' => 'Contenance', 'values' => '25 ml, 30 ml']],
        ]);
        $this->assertSame([['name' => 'Contenance', 'values' => ['25 ml', '30 ml']]], $category->fresh()->option_template);

        $this->put(route('admin.categories.update', $category->id), ['name' => 'soins', 'option_template' => [['name' => '', 'values' => '']]]);
        $this->assertNull($category->fresh()->option_template);
    }
}
