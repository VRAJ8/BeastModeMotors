<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BodyType: string implements HasColor, HasLabel
{
    case Coupe = 'coupe';
    case Convertible = 'convertible';
    case Sedan = 'sedan';
    case Suv = 'suv';
    case Hypercar = 'hypercar';
    case GrandTourer = 'grand_tourer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coupe => 'Coupe',
            self::Convertible => 'Convertible',
            self::Sedan => 'Sedan',
            self::Suv => 'SUV',
            self::Hypercar => 'Hypercar',
            self::GrandTourer => 'Grand Tourer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Coupe => 'gray',
            self::Convertible => 'gray',
            self::Sedan => 'gray',
            self::Suv => 'gray',
            self::Hypercar => 'primary',
            self::GrandTourer => 'gray',
        };
    }
}
