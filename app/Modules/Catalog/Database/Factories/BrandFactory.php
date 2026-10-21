<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
        ];
    }
}
