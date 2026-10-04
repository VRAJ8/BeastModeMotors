<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Brand>
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
