<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FuelType: string implements HasColor, HasLabel
{
    case Petrol = 'petrol';
    case Hybrid = 'hybrid';
    case Electric = 'electric';
    case Diesel = 'diesel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Petrol => 'Petrol',
            self::Hybrid => 'Hybrid',
            self::Electric => 'Electric',
            self::Diesel => 'Diesel',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Petrol => 'gray',
            self::Hybrid => 'info',
            self::Electric => 'success',
            self::Diesel => 'gray',
        };
    }
}
