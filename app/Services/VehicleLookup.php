<?php

namespace App\Services;

/**
 * Turns a VIN into car details: NHTSA when reachable, the offline decoder otherwise.
 */
class VehicleLookup
{
    public function __construct(private VinDecoder $decoder, private Nhtsa $nhtsa) {}

    /**
     * @return array{vin: string, valid: bool, check_digit: ?string, country: ?string, source: string, details: array<string, mixed>}
     */
    public function lookup(string $vin): array
    {
        $offline = $this->decoder->decode($vin);
        $details = $offline['valid'] || $this->decoder->hasValidFormat($vin) ? $this->nhtsa->decodeVin($offline['vin']) : null;

        return [
            'vin' => $offline['vin'],
            'valid' => $offline['valid'],
            'check_digit' => $offline['check_digit'],
            'country' => $details['plant_country'] ?? $offline['country'],
            'source' => $details ? 'nhtsa' : 'offline',
            'details' => $details ?? array_filter([
                'year' => $offline['year'],
                'make' => $offline['make'],
            ]),
        ];
    }
}
