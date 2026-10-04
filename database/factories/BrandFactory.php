<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'country' => fake()->country(),
            'founded_year' => fake()->numberBetween(1900, 2010),
            'description' => fake()->paragraph(),
        ];
    }
}
