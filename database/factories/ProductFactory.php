<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            // Define default values for product attributes
            'name' => $this->faker->word(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 12000,100000),
            'category_id' => \App\Models\Category::factory(),
            'quantity_stock' => $this->faker->numberBetween(13, 30),
            'color_id' => \App\Models\Color::factory(),
            'alert_stock' => $this->faker->numberBetween(1, 10),
        ];
    }
}