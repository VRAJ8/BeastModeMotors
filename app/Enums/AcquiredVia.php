<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Acquired via.
 */
enum AcquiredVia: string implements HasColor, HasLabel
{
    case Dealer = 'dealer';
    case PrivateSale = 'private_sale';
    case Platform = 'platform';
    case New = 'new';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dealer => 'Bought from a dealer',
            self::PrivateSale => 'Private sale',
            self::Platform => 'Sold on Beast Mode Motors',
            self::New => 'Bought new',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dealer => 'gray',
            self::PrivateSale => 'gray',
            self::Platform => 'primary',
            self::New => 'success',
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
