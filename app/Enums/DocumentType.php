<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Document type.
 */
enum DocumentType: string implements HasColor, HasLabel
{
    case Receipt = 'receipt';
    case InspectionReport = 'inspection_report';
    case Warranty = 'warranty';
    case Manual = 'manual';
    case Title = 'title';
    case Registration = 'registration';
    case Insurance = 'insurance';
    case Photo = 'photo';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt / invoice',
            self::InspectionReport => 'Inspection report',
            self::Warranty => 'Warranty',
            self::Manual => 'Manual / booklet',
            self::Title => 'Title',
            self::Registration => 'Registration',
            self::Insurance => 'Insurance',
            self::Photo => 'Photo',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Receipt => 'success',
            self::InspectionReport => 'info',
            self::Warranty => 'info',
            self::Manual => 'gray',
            self::Title => 'warning',
            self::Registration => 'warning',
            self::Insurance => 'warning',
            self::Photo => 'gray',
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

    /**
     * Whether the document follows the car to its next owner.
     *
     * Proof of work stays with the car; personal paperwork stays with the person.
     */
    public function transfersWithCar(): bool
    {
        return in_array($this, [self::Receipt, self::InspectionReport, self::Warranty, self::Manual, self::Photo], true);
    }

    /**
     * Whether the document typically has an expiry date worth reminding about.
     */
    public function expires(): bool
    {
        return in_array($this, [self::Registration, self::Insurance, self::Warranty, self::InspectionReport], true);
    }
}
