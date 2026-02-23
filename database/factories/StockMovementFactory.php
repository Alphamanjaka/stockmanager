<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['in', 'out']);
        $qty = $this->faker->numberBetween(1, 100);
        $signedQty = $type === 'in' ? $qty : -$qty;
        $before = $this->faker->numberBetween(100, 500);

        return [
            'product_id' => \App\Models\Product::factory(),
            'type' => $type,
            'quantity' => $signedQty,
            'stock_before' => $before,
            'stock_after' => $before + $signedQty,
            'reason' => $this->faker->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
