<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Services\CategoryService;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoryService = app(CategoryService::class);
        $categories = $categoryService->getAllCategory([],false);
        return [
            // Define default values for product attributes
            'name' => $this->faker->word(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 12000,100000),
            'category_id' => $categories->random()->id,
            'quantity_stock' => $this->faker->numberBetween(13, 30),
        ];
    }
}