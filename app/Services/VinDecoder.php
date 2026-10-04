<?php

namespace App\Services;

/**
 * Offline VIN validation and decoding (ISO 3779 / 49 CFR 565).
 *
 * Validates the check digit and decodes manufacturer, country and model year without
 * any network access, so the app keeps working when NHTSA is unreachable.
 */
class VinDecoder
{
    private const WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    private const TRANSLITERATION = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
        'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
        'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
    ];

    private const YEAR_CODES = 'ABCDEFGHJKLMNPRSTVWXY123456789';

    /** World manufacturer identifiers for the brands people most often ask about. */
    private const WMI = [
        '1HG' => 'Honda', 'JHM' => 'Honda', '2HG' => 'Honda', '5FN' => 'Honda', '19X' => 'Honda', '19U' => 'Acura', 'JH4' => 'Acura',
        '1FA' => 'Ford', '1FM' => 'Ford', '1FT' => 'Ford', '3FA' => 'Ford', '1LN' => 'Lincoln', '5LM' => 'Lincoln',
        '1G1' => 'Chevrolet', '1GC' => 'Chevrolet', '1GN' => 'Chevrolet', '2G1' => 'Chevrolet', '3GN' => 'Chevrolet',
        '1GT' => 'GMC', '1G6' => 'Cadillac', '1GY' => 'Cadillac',
        '1C4' => 'Jeep', '1C6' => 'Ram', '2C3' => 'Dodge', '1C3' => 'Chrysler', '2C4' => 'Chrysler',
        '1N4' => 'Nissan', '1N6' => 'Nissan', 'JN1' => 'Nissan', 'JN8' => 'Nissan', '5N1' => 'Nissan',
        '4T1' => 'Toyota', '4T3' => 'Toyota', '5TD' => 'Toyota', '5TF' => 'Toyota', '2T1' => 'Toyota', 'JTD' => 'Toyota',
        'JTE' => 'Toyota', 'JTM' => 'Toyota', 'JTN' => 'Toyota', 'JTH' => 'Lexus', '2T2' => 'Lexus',
        '5YJ' => 'Tesla', '7SA' => 'Tesla', 'LRW' => 'Tesla', '7G2' => 'Tesla',
        'WBA' => 'BMW', 'WBS' => 'BMW', 'WBY' => 'BMW', '5UX' => 'BMW', '5YM' => 'BMW', 'WMW' => 'MINI',
        'WDD' => 'Mercedes-Benz', 'WDC' => 'Mercedes-Benz', 'W1K' => 'Mercedes-Benz', 'W1N' => 'Mercedes-Benz', '4JG' => 'Mercedes-Benz',
        'WAU' => 'Audi', 'WA1' => 'Audi', 'WUA' => 'Audi',
        'WP0' => 'Porsche', 'WP1' => 'Porsche',
        'WVW' => 'Volkswagen', 'WVG' => 'Volkswagen', '3VW' => 'Volkswagen', '1VW' => 'Volkswagen',
        'ZFF' => 'Ferrari', 'ZHW' => 'Lamborghini', 'ZAM' => 'Maserati', 'ZAR' => 'Alfa Romeo', 'SBM' => 'McLaren',
        'SCF' => 'Aston Martin', 'SCB' => 'Bentley', 'SCA' => 'Rolls-Royce', 'SAL' => 'Land Rover', 'SAJ' => 'Jaguar',
        'KMH' => 'Hyundai', '5NP' => 'Hyundai', 'KM8' => 'Hyundai', 'KMU' => 'Genesis', 'KNA' => 'Kia', 'KND' => 'Kia', '5XY' => 'Kia',
        'JF1' => 'Subaru', 'JF2' => 'Subaru', '4S3' => 'Subaru', '4S4' => 'Subaru',
        'JM1' => 'Mazda', 'JM3' => 'Mazda', 'YV1' => 'Volvo', 'YV4' => 'Volvo', '7JR' => 'Volvo', 'LVY' => 'Volvo',
        'JA3' => 'Mitsubishi', 'JA4' => 'Mitsubishi', '7FC' => 'Rivian', '50E' => 'Lucid', 'YSM' => 'Polestar',
    ];

    private const COUNTRIES = [
        '1' => 'United States', '4' => 'United States', '5' => 'United States', '7' => 'United States',
        '2' => 'Canada', '3' => 'Mexico', 'J' => 'Japan', 'K' => 'South Korea', 'L' => 'China',
        'S' => 'United Kingdom', 'V' => 'France / Spain', 'W' => 'Germany', 'Y' => 'Sweden / Finland', 'Z' => 'Italy',
    ];

    public static function normalize(string $vin): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $vin) ?? '');
    }

    public function hasValidFormat(string $vin): bool
    {
        return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', self::normalize($vin));
    }

    public function expectedCheckDigit(string $vin): ?string
    {
        $vin = self::normalize($vin);

        if (! $this->hasValidFormat($vin)) {
            return null;
        }

        $sum = 0;

        foreach (str_split($vin) as $i => $char) {
            $value = ctype_digit($char) ? (int) $char : self::TRANSLITERATION[$char];
            $sum += $value * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;

        return $remainder === 10 ? 'X' : (string) $remainder;
    }

    public function isValid(string $vin): bool
    {
        $vin = self::normalize($vin);

        return $this->hasValidFormat($vin) && $vin[8] === $this->expectedCheckDigit($vin);
    }

    /**
     * Return the VIN with its check digit corrected (used to generate valid demo VINs).
     */
    public function withCheckDigit(string $vin): string
    {
        $vin = self::normalize($vin);

        return substr_replace($vin, (string) $this->expectedCheckDigit($vin), 8, 1);
    }

    public function modelYear(string $vin, ?int $currentYear = null): ?int
    {
        $vin = self::normalize($vin);
        $index = strpos(self::YEAR_CODES, $vin[9] ?? '?');

        if ($index === false) {
            return null;
        }

        $currentYear ??= (int) now()->year;

        // For cars built for North America, an alphabetic 7th character means 2010 or later.
        $year = 1980 + $index + (ctype_alpha($vin[6]) ? 30 : 0);

        while ($year > $currentYear + 1) {
            $year -= 30;
        }

        return $year;
    }

    public function manufacturer(string $vin): ?string
    {
        return self::WMI[substr(self::normalize($vin), 0, 3)] ?? null;
    }

    public function country(string $vin): ?string
    {
        return self::COUNTRIES[substr(self::normalize($vin), 0, 1)] ?? null;
    }

    /**
     * @return array{vin: string, valid: bool, check_digit: ?string, year: ?int, make: ?string, country: ?string}
     */
    public function decode(string $vin): array
    {
        $vin = self::normalize($vin);
        $format = $this->hasValidFormat($vin);

        return [
            'vin' => $vin,
            'valid' => $format && $this->isValid($vin),
            'check_digit' => $format ? $this->expectedCheckDigit($vin) : null,
            'year' => $format ? $this->modelYear($vin) : null,
            'make' => $format ? $this->manufacturer($vin) : null,
            'country' => $format ? $this->country($vin) : null,
        ];
    }
}
