<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Odometer source.
 */
enum OdometerSource: string implements HasColor, HasLabel
{
    case Purchase = 'purchase';
    case Service = 'service';
    case Manual = 'manual';
    case Expense = 'expense';
    case Inspection = 'inspection';
    case Sale = 'sale';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchase => 'At purchase',
            self::Service => 'Service record',
            self::Manual => 'Owner entry',
            self::Expense => 'Fuel / charge',
            self::Inspection => 'Inspection',
            self::Sale => 'At sale',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Purchase => 'gray',
            self::Service => 'success',
            self::Manual => 'gray',
            self::Expense => 'gray',
            self::Inspection => 'info',
            self::Sale => 'primary',
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
