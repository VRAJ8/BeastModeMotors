<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadType: string implements HasColor, HasLabel
{
    case General = 'general';
    case Vehicle = 'vehicle';
    case Finance = 'finance';
    case Offer = 'offer';
    case TradeIn = 'trade_in';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Vehicle => 'Vehicle Enquiry',
            self::Finance => 'Finance',
            self::Offer => 'Offer',
            self::TradeIn => 'Trade-in',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::General => 'gray',
            self::Vehicle => 'info',
            self::Finance => 'primary',
            self::Offer => 'warning',
            self::TradeIn => 'success',
        };
    }
}
