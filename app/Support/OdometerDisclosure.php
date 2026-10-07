<?php

namespace App\Support;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * When federal law requires an odometer disclosure at a sale (49 CFR 580.17, as amended in 2020):
 * model year 2011 and newer, until the vehicle is 20 years old. Model year 2010 and older are exempt.
 */
class OdometerDisclosure
{
    public const FIRST_COVERED_MODEL_YEAR = 2011;

    public const COVERED_YEARS = 20;

    public static function required(Vehicle $vehicle, ?Carbon $on = null): bool
    {
        $on ??= now();

        return $vehicle->year >= self::FIRST_COVERED_MODEL_YEAR
            && $on->year - $vehicle->year < self::COVERED_YEARS;
    }

    /**
     * Why no federal statement is needed, for the deal room.
     */
    public static function exemption(Vehicle $vehicle, ?Carbon $on = null): ?string
    {
        if (self::required($vehicle, $on)) {
            return null;
        }

        return $vehicle->year < self::FIRST_COVERED_MODEL_YEAR
            ? "Model year {$vehicle->year} cars are exempt from the federal odometer disclosure (it covers 2011 and newer)."
            : 'Cars 20 or more model years old are exempt from the federal odometer disclosure.';
    }
}
