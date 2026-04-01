<?php

namespace Tests\Feature\Services;

use App\Models\Color;
use App\Services\ColorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ColorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Initialisation du service avec une instance du modèle
        $this->service = new ColorService(new Color());
    }

    /** @test */
    public function it_can_create_a_color()
    {
        $data = ['name' => 'Bleu Royal', 'code' => '#002366'];

        $color = $this->service->create($data);

        $this->assertInstanceOf(Color::class, $color);
        $this->assertEquals('Bleu Royal', $color->name);
        $this->assertDatabaseHas('colors', $data);
    }

    /** @test */
    public function it_can_update_a_color()
    {
        $color = Color::create(['name' => 'Rouge', 'code' => '#FF0000']);
        $newData = ['name' => 'Rouge Cerise'];

        $updatedColor = $this->service->update($color->id, $newData);

        $this->assertEquals('Rouge Cerise', $updatedColor->name);
        $this->assertDatabaseHas('colors', ['id' => $color->id, 'name' => 'Rouge Cerise']);
    }

    /** @test */
    public function it_can_delete_a_color()
    {
        $color = Color::create(['name' => 'Jaune', 'code' => '#FFFF00']);

        $result = $this->service->delete($color->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('colors', ['id' => $color->id]);
    }

    /** @test */
    public function it_can_get_color_by_id()
    {
        $color = Color::create(['name' => 'Vert', 'code' => '#008000']);

        $foundColor = $this->service->getById($color->id);

        $this->assertEquals($color->id, $foundColor->id);
        $this->assertEquals('Vert', $foundColor->name);
    }


    /** @test */
    public function it_applies_default_sort_by_name_for_colors()
    {
        Color::create(['name' => 'Zebra Black', 'code' => '#000000']);
        Color::create(['name' => 'Azure Blue', 'code' => '#F0FFFF']);
        Color::create(['name' => 'Crimson', 'code' => '#DC143C']);

        // getAllColors() impose le tri par 'name' s'il n'est pas fourni
        $results = $this->service->getAllColors([], false); // false pour obtenir une collection

        $this->assertEquals('Azure Blue', $results->first()->name);
        $this->assertEquals('Zebra Black', $results->last()->name);
    }

    /** @test */
    public function it_respects_custom_sorting_parameters()
    {
        Color::create(['name' => 'A', 'code' => '#1']);
        Color::create(['name' => 'B', 'code' => '#2']);

        $results = $this->service->getAllColors([
            'sort' => 'name',
            'order' => 'desc'
        ], false);

        $this->assertEquals('B', $results->first()->name);
    }

    /** @test */
    public function it_can_filter_colors_by_search_term()
    {
        // Arrange
        Color::create(['name' => 'Bleu Marine', 'code' => '#000080']);
        Color::create(['name' => 'Rouge Sang', 'code' => '#850606']);
        Color::create(['name' => 'Vert Forêt', 'code' => '#228B22']);

        // Act: Recherche d'une couleur contenant "Bleu"
        $results = $this->service->getAllColors(['search' => 'Bleu'], false);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Bleu Marine', $results->first()->name);
    }
}
