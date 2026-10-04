<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Enums\FuelType;
use App\Enums\OdometerSource;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Adds a car to someone's garage: the vehicle, their ownership, the first reading and a maintenance plan.
 */
class Garage
{
    public function __construct(private VinDecoder $decoder, private MaintenancePlanner $planner) {}

    /**
     * @param  array{vin: string, year: int, make: string, model: string, trim?: ?string, body?: ?string, engine?: ?string, drivetrain?: ?string, transmission?: ?string, fuel_type: string, exterior_color?: ?string, nickname?: ?string, decoded?: ?array, decode_source?: ?string}  $car
     * @param  array{acquired_via: string, started_on: string, start_mileage: int, current_mileage: int, purchase_price_cents?: ?int}  $ownership
     */
    public function register(User $user, array $car, array $ownership): Vehicle
    {
        return DB::transaction(function () use ($user, $car, $ownership) {
            $vin = VinDecoder::normalize($car['vin']);

            $vehicle = $user->vehicles()->create([
                ...$car,
                'vin' => $vin,
                'vin_valid' => $this->decoder->isValid($vin),
                'current_mileage' => max($ownership['start_mileage'], $ownership['current_mileage']),
            ]);

            $owned = $vehicle->ownerships()->create([
                'user_id' => $user->getKey(),
                'owner_number' => 1,
                'acquired_via' => AcquiredVia::from($ownership['acquired_via']),
                'started_on' => $ownership['started_on'],
                'start_mileage' => $ownership['start_mileage'],
                'purchase_price_cents' => $ownership['purchase_price_cents'] ?? null,
            ]);

            $vehicle->readings()->create([
                'ownership_id' => $owned->getKey(),
                'reading' => $ownership['start_mileage'],
                'recorded_on' => $ownership['started_on'],
                'source' => OdometerSource::Purchase,
            ]);

            if ($ownership['current_mileage'] > $ownership['start_mileage']) {
                $vehicle->readings()->create([
                    'ownership_id' => $owned->getKey(),
                    'reading' => $ownership['current_mileage'],
                    'recorded_on' => Carbon::today(),
                    'source' => OdometerSource::Manual,
                ]);
            }

            $this->planner->createDefaults($vehicle, FuelType::from($car['fuel_type']));

            return $vehicle;
        });
    }
}
