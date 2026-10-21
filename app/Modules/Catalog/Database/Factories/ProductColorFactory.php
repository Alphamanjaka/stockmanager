<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductColor>
 */
class ProductColorFactory extends Factory
{
    protected $model = ProductColor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'color_id' => Color::factory(),
            'stock' => $this->faker->numberBetween(10, 200),
            'alert_stock' => $this->faker->numberBetween(5, 20),
            'price' => $this->faker->randomFloat(2, 12000, 100000),

        ];
    }
}
