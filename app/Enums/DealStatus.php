<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Deal status.
 */
enum DealStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Agreed = 'agreed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Negotiating',
            self::Agreed => 'Price agreed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Agreed => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Agreed], true);
    }
}
