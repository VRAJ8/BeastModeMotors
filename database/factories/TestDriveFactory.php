<?php

namespace Database\Factories;

use App\Enums\TestDriveStatus;
use App\Models\TestDrive;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestDrive>
 */
class TestDriveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'scheduled_at' => now()->addDays(3)->setTime(11, 0),
            'status' => TestDriveStatus::Pending,
        ];
    }
}
