<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    protected $model = Color::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Define default values for color attributes
            'name' => $this->faker->unique()->name(),
            'code' => $this->faker->hexColor(),
        ];
    }
}
