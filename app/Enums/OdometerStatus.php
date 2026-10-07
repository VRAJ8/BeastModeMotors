<?php

namespace App\Enums;

/**
 * The seller's odometer certification at a sale, as the federal disclosure statement words it (49 CFR 580.5).
 */
enum OdometerStatus: string
{
    case Actual = 'actual';
    case ExceedsLimits = 'exceeds_limits';
    case NotActual = 'not_actual';

    public function label(): string
    {
        return match ($this) {
            self::Actual => 'Actual mileage',
            self::ExceedsLimits => 'Exceeds the odometer\'s mechanical limits',
            self::NotActual => 'Not the actual mileage',
        };
    }

    public function explanation(): string
    {
        return match ($this) {
            self::Actual => 'The odometer shows the miles the car has actually driven.',
            self::ExceedsLimits => 'The odometer has rolled over past its highest reading (common on older five-digit odometers).',
            self::NotActual => 'The reading is wrong: the odometer or cluster was replaced, broken, or tampered with.',
        };
    }

    public function certification(): string
    {
        return match ($this) {
            self::Actual => 'I hereby certify that to the best of my knowledge the odometer reading reflects the actual mileage of the vehicle described herein.',
            self::ExceedsLimits => 'I hereby certify that to the best of my knowledge the odometer reading reflects the amount of mileage in excess of its mechanical limits.',
            self::NotActual => 'I hereby certify that the odometer reading is NOT the actual mileage. WARNING — ODOMETER DISCREPANCY.',
        };
    }
}
