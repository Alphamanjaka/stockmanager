<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

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
            'category_id' => fn () => Category::inRandomOrder()->first()?->id ?? Category::factory(),
        ];
    }
}
