<?php

namespace App\Modules\Contact\Database\Factories;

use App\Modules\Contact\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->optional()->address(),
            'notes' => null,
        ];
    }
}
