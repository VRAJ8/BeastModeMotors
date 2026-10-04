<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Transmission: string implements HasColor, HasLabel
{
    case Automatic = 'automatic';
    case DualClutch = 'dual_clutch';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic',
            self::DualClutch => 'Dual-Clutch',
            self::Manual => 'Manual',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Automatic => 'gray',
            self::DualClutch => 'gray',
            self::Manual => 'gray',
        };
    }
}
