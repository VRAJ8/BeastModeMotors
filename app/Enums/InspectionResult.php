<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Inspection result.
 */
enum InspectionResult: string implements HasColor, HasLabel
{
    case Pass = 'pass';
    case Attention = 'attention';
    case Fail = 'fail';
    case NotChecked = 'not_checked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pass => 'Good',
            self::Attention => 'Needs attention',
            self::Fail => 'Problem',
            self::NotChecked => 'Not checked',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pass => 'success',
            self::Attention => 'warning',
            self::Fail => 'danger',
            self::NotChecked => 'gray',
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
