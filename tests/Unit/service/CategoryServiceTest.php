<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase; // Indispensable pour repartir d'une base propre

    protected CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryService();
    }

    /** @test */
    public function it_can_create_a_category_and_sync_children()
    {
        // Arrange: On crée des catégories qui deviendront enfants
        $child1 = Category::create(['name' => 'Enfant 1']);
        $child2 = Category::create(['name' => 'Enfant 2']);

        $data = [
            'name' => 'Parent Tech',
            'children' => [$child1->id, $child2->id]
        ];

        // Act: Appel du service
        $category = $this->service->create($data);

        // Assert: Vérifications
        $this->assertDatabaseHas('categories', ['name' => 'Parent Tech']);
        $this->assertEquals($category->id, $child1->fresh()->parent_id);
        $this->assertEquals($category->id, $child2->fresh()->parent_id);
    }

    /** @test */
    public function it_throws_exception_when_deleting_category_with_children()
    {
        // Arrange
        $parent = Category::create(['name' => 'Parent']);
        Category::create(['name' => 'Enfant', 'parent_id' => $parent->id]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot delete category with child categories.");

        $this->service->destroy($parent->id);
    }

    /** @test */
    public function it_calculates_correct_stats()
    {
        // Arrange
        $cat = Category::create(['name' => 'Hardware']);
        Product::create(['name' => 'Mouse', 'category_id' => $cat->id, 'price' => 10, 'stock' => 5]);
        Product::create(['name' => 'Keyboard', 'category_id' => $cat->id, 'price' => 10, 'stock' => 5]);

        // Act
        $stats = $this->service->getCategoryStats();

        // Assert
        $this->assertEquals(1, $stats['total_categories']);
        $this->assertEquals(2, $stats['total_products_linked']);
        $this->assertEquals('Hardware', $stats['most_populated']->name);
    }

    /** @test */
    public function it_filters_categories_by_search_term()
    {
        // Arrange
        Category::create(['name' => 'Informatique']);
        Category::create(['name' => 'Cuisine']);

        // Act
        $results = $this->service->getAllCategory(['search' => 'Info'], false);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Informatique', $results->first()->name);
    }
}