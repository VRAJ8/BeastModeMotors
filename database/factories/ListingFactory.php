<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'seller_id' => fn (array $attributes) => Vehicle::find($attributes['vehicle_id'])->user_id,
            'status' => ListingStatus::Active,
            'price_cents' => 2_450_000,
            'mileage' => fn (array $attributes) => Vehicle::find($attributes['vehicle_id'])->current_mileage,
            'city' => 'Austin',
            'state' => 'TX',
            'description' => 'One careful owner, garaged, every service done on time with receipts in the passport.',
            'score' => 60,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ListingStatus::Draft, 'published_at' => null]);
    }
}
