<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Report reason.
 */
enum ReportReason: string implements HasColor, HasLabel
{
    case Scam = 'scam';
    case Misrepresented = 'misrepresented';
    case Duplicate = 'duplicate';
    case Inappropriate = 'inappropriate';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scam => 'Looks like a scam',
            self::Misrepresented => 'Car is misrepresented',
            self::Duplicate => 'Duplicate listing',
            self::Inappropriate => 'Inappropriate content',
            self::Other => 'Something else',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scam => 'danger',
            self::Misrepresented => 'warning',
            self::Duplicate => 'gray',
            self::Inappropriate => 'warning',
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
