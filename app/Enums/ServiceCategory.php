<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Service category.
 */
enum ServiceCategory: string implements HasColor, HasLabel
{
    case Maintenance = 'maintenance';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Tires = 'tires';
    case Bodywork = 'bodywork';
    case Modification = 'modification';
    case Detailing = 'detailing';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::Repair => 'Repair',
            self::Inspection => 'Inspection',
            self::Tires => 'Tires & wheels',
            self::Bodywork => 'Body & paint',
            self::Modification => 'Modification',
            self::Detailing => 'Detailing',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Maintenance => 'success',
            self::Repair => 'warning',
            self::Inspection => 'info',
            self::Tires => 'gray',
            self::Bodywork => 'danger',
            self::Modification => 'primary',
            self::Detailing => 'gray',
            self::Other => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
