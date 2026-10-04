<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Drivetrain: string implements HasColor, HasLabel
{
    case Rwd = 'rwd';
    case Awd = 'awd';
    case Fwd = 'fwd';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rwd => 'RWD',
            self::Awd => 'AWD',
            self::Fwd => 'FWD',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Rwd => 'gray',
            self::Awd => 'gray',
            self::Fwd => 'gray',
        };
    }
}
