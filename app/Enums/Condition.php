<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Condition: string implements HasColor, HasLabel
{
    case New = 'new';
    case Certified = 'certified';
    case PreOwned = 'pre_owned';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Certified => 'Certified Pre-Owned',
            self::PreOwned => 'Pre-Owned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'success',
            self::Certified => 'info',
            self::PreOwned => 'gray',
        };
    }
}
