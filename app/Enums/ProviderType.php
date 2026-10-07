<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Who did the work.
 */
enum ProviderType: string implements HasColor, HasLabel
{
    case Dealer = 'dealer';
    case Independent = 'independent';
    case Diy = 'diy';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dealer => 'Franchise dealer',
            self::Independent => 'Independent shop',
            self::Diy => 'Owner (DIY)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dealer => 'info',
            self::Independent => 'success',
            self::Diy => 'gray',
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
