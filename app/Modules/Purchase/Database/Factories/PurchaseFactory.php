<?php

namespace App\Modules\Purchase\Database\Factories;

use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
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
            'supplier_id' => Supplier::factory(),
            'reference' => 'PUR-'.$this->faker->unique()->numerify('########'),
            'total_amount' => $total,
            'discount' => $discount,
            'total_net' => $total - $discount,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
