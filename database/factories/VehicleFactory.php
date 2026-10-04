<?php

namespace Database\Factories;

use App\Enums\BodyType;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'model' => ucfirst(fake()->unique()->word()).' '.fake()->randomElement(['GT', 'S', 'RS', 'Turbo', 'V12']),
            'year' => fake()->numberBetween(2018, (int) date('Y')),
            'price' => fake()->numberBetween(80, 600) * 1000,
            'mileage' => fake()->numberBetween(0, 30000),
            'body_type' => fake()->randomElement(BodyType::cases()),
            'condition' => fake()->randomElement(Condition::cases()),
            'status' => VehicleStatus::Available,
            'fuel_type' => FuelType::Petrol,
            'transmission' => Transmission::DualClutch,
            'drivetrain' => fake()->randomElement(Drivetrain::cases()),
            'engine' => fake()->randomElement(['4.0L Twin-Turbo V8', '6.5L V12', '3.8L Twin-Turbo Flat-6']),
            'horsepower' => fake()->numberBetween(400, 1000),
            'torque' => fake()->numberBetween(350, 800),
            'zero_to_sixty' => fake()->randomFloat(1, 2.3, 4.5),
            'top_speed' => fake()->numberBetween(170, 220),
            'exterior_color' => fake()->safeColorName(),
            'interior_color' => 'Black',
            'description' => fake()->paragraph(),
            'features' => ['Carbon ceramic brakes', 'Launch control'],
            'images' => [],
            'is_featured' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function sold(): static
    {
        return $this->state(['status' => VehicleStatus::Sold]);
    }

    public function draft(): static
    {
        return $this->state(['published_at' => null]);
    }
}
