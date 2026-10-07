<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Expense category.
 */
enum ExpenseCategory: string implements HasColor, HasLabel
{
    case Fuel = 'fuel';
    case Charging = 'charging';
    case Insurance = 'insurance';
    case Registration = 'registration';
    case Parking = 'parking';
    case Cleaning = 'cleaning';
    case Accessories = 'accessories';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fuel => 'Fuel',
            self::Charging => 'Charging',
            self::Insurance => 'Insurance',
            self::Registration => 'Registration & taxes',
            self::Parking => 'Parking & tolls',
            self::Cleaning => 'Cleaning',
            self::Accessories => 'Accessories',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fuel => 'warning',
            self::Charging => 'info',
            self::Insurance => 'primary',
            self::Registration => 'gray',
            self::Parking => 'gray',
            self::Cleaning => 'gray',
            self::Accessories => 'gray',
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

    /**
     * Unit for the optional volume field (fuel in gallons, charging in kWh).
     */
    public function volumeUnit(): ?string
    {
        return match ($this) {
            self::Fuel => 'gal',
            self::Charging => 'kWh',
            default => null,
        };
    }
}
