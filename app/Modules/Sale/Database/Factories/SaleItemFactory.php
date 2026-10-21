<?php

namespace App\Modules\Sale\Database\Factories;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_color_id' => ProductColor::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'subtotal' => 0, // Sera calculé (qty * price)
        ];
    }
}
