<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Listing status.
 */
enum ListingStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case Pending = 'pending';
    case Sold = 'sold';
    case Withdrawn = 'withdrawn';
    case Removed = 'removed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'For sale',
            self::Pending => 'Sale agreed',
            self::Sold => 'Sold',
            self::Withdrawn => 'Withdrawn',
            self::Removed => 'Removed by moderator',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Pending => 'warning',
            self::Sold => 'primary',
            self::Withdrawn => 'gray',
            self::Removed => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function isPublic(): bool
    {
        return in_array($this, [self::Active, self::Pending], true);
    }
}
