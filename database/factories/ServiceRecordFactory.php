<?php

namespace Database\Factories;

use App\Enums\ProviderType;
use App\Enums\ServiceCategory;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRecord>
 */
class ServiceRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'ownership_id' => fn (array $attributes) => Vehicle::find($attributes['vehicle_id'])?->currentOwnership?->getKey(),
            'category' => ServiceCategory::Maintenance,
            'title' => 'Oil & filter change',
            'performed_on' => now()->subWeek()->toDateString(),
            'mileage' => 28000,
            'cost_cents' => 8900,
            'provider_type' => ProviderType::Independent,
            'provider_name' => 'Main Street Auto',
            'provider_email' => 'service@mainstreetauto.test',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verified_at' => now()]);
    }
}
