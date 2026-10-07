<?php

namespace Database\Factories;

use App\Enums\AcquiredVia;
use App\Enums\FuelType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VinDecoder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vin' => self::vin(),
            'vin_valid' => true,
            'decode_source' => 'offline',
            'year' => 2019,
            'make' => 'Honda',
            'model' => 'Civic',
            'trim' => 'EX',
            'fuel_type' => FuelType::Gasoline,
            'exterior_color' => 'Blue',
            'current_mileage' => 30000,
        ];
    }

    /**
     * A random VIN with a correct check digit.
     */
    public static function vin(string $prefix = '1HGCM82'): string
    {
        $chars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = str_pad($prefix, 8, 'A').'0K';

        while (strlen($vin) < 17) {
            $vin .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return app(VinDecoder::class)->withCheckDigit($vin);
    }

    /**
     * Give the car a current ownership (and an opening odometer reading), as the app does on registration.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Vehicle $vehicle) {
            if ($vehicle->ownerships()->exists()) {
                return;
            }

            $ownership = $vehicle->ownerships()->create([
                'user_id' => $vehicle->user_id,
                'owner_number' => 1,
                'acquired_via' => AcquiredVia::Dealer,
                'started_on' => now()->subYears(2)->toDateString(),
                'start_mileage' => max(0, $vehicle->current_mileage - 20000),
            ]);

            $vehicle->readings()->create([
                'ownership_id' => $ownership->getKey(),
                'reading' => $ownership->start_mileage,
                'recorded_on' => $ownership->started_on,
                'source' => 'purchase',
            ]);

            if ($vehicle->current_mileage > $ownership->start_mileage) {
                $vehicle->readings()->create([
                    'ownership_id' => $ownership->getKey(),
                    'reading' => $vehicle->current_mileage,
                    'recorded_on' => now()->subWeek()->toDateString(),
                    'source' => 'manual',
                ]);
            }
        });
    }
}
