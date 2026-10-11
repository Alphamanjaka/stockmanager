<?php

namespace App\Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Purchase\Models\PurchaseItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Purchase\Models\PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'purchase_id' => \App\Modules\Purchase\Models\Purchase::factory(),
            'product_color_id' => \App\Modules\Catalog\Models\ProductColor::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'subtotal' => 0, // Sera calculé (qty * price)
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),

        ];
    }
}
