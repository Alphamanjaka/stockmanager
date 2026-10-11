<?php

namespace App\Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Catalog\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Models\Product>
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
            'category_id' => fn() => \App\Modules\Catalog\Models\Category::inRandomOrder()->first()?->id ?? \App\Modules\Catalog\Models\Category::factory(),
        ];
    }

}
