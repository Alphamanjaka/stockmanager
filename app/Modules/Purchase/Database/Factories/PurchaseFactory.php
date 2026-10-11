<?php

namespace App\Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Purchase\Models\Purchase;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Purchase\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = $this->faker->randomFloat(2, 500, 10000);
        $discount = $this->faker->randomFloat(2, 0, 100);

        return [
            'supplier_id' => \App\Modules\Contact\Models\Supplier::factory(),
            'reference' => 'PUR-' . $this->faker->unique()->numerify('########'),
            'total_amount' => $total,
            'discount' => $discount,
            'total_net' => $total - $discount,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
