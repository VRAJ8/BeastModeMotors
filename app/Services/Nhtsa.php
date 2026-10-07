<?php

namespace App\Services;

use App\Enums\FuelType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Client for NHTSA's free vehicle APIs (vPIC VIN decoding and safety recalls).
 *
 * Every method returns null when the service is disabled or unreachable, so callers can fall back.
 */
class Nhtsa
{
    private const ACRONYMS = ['BMW', 'GMC', 'MINI', 'RAM', 'AMG', 'SRT', 'GT', 'RS', 'AWD', 'FWD', 'RWD', 'EV', 'SUV'];

    /**
     * @return array<string, mixed>|null
     */
    public function decodeVin(string $vin): ?array
    {
        if (! config('passport.nhtsa.enabled')) {
            return null;
        }

        try {
            $row = Cache::remember("nhtsa:vin:{$vin}", now()->addDays(30), function () use ($vin) {
                return Http::timeout(config('passport.nhtsa.timeout'))
                    ->get(config('passport.nhtsa.vin_url').'/'.$vin, ['format' => 'json'])
                    ->throw()
                    ->json('Results.0');
            });
        } catch (Throwable $e) {
            Log::warning('NHTSA VIN decode failed', ['vin' => $vin, 'error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($row) || blank($row['Make'] ?? null) || blank($row['ModelYear'] ?? null)) {
            return null;
        }

        $engine = collect([
            filled($row['DisplacementL'] ?? null) ? round((float) $row['DisplacementL'], 1).'L' : null,
            filled($row['EngineCylinders'] ?? null) ? $row['EngineCylinders'].'-cyl' : null,
            filled($row['EngineHP'] ?? null) ? round((float) $row['EngineHP']).' hp' : null,
        ])->filter()->implode(' ');

        return [
            'year' => (int) $row['ModelYear'],
            'make' => $this->titleCase($row['Make']),
            'model' => (string) ($row['Model'] ?? ''),
            'trim' => ($row['Trim'] ?? null) ?: ($row['Series'] ?? null) ?: null,
            'body' => ($row['BodyClass'] ?? null) ?: null,
            'engine' => $engine ?: null,
            'drivetrain' => ($row['DriveType'] ?? null) ?: null,
            'transmission' => ($row['TransmissionStyle'] ?? null) ?: null,
            'fuel_type' => FuelType::fromNhtsa($row['FuelTypePrimary'] ?? null, $row['ElectrificationLevel'] ?? null)->value,
            'plant_country' => ($row['PlantCountry'] ?? null) ?: null,
        ];
    }

    /**
     * @return list<array{campaign_number: string, component: string, summary: string, consequence: ?string, remedy: ?string, reported_on: ?string}>|null
     */
    public function recalls(string $make, string $model, int $year): ?array
    {
        if (! config('passport.nhtsa.enabled')) {
            return null;
        }

        try {
            $results = Http::timeout(config('passport.nhtsa.timeout'))
                ->get(config('passport.nhtsa.recalls_url'), ['make' => $make, 'model' => $model, 'modelYear' => $year])
                ->throw()
                ->json('results');
        } catch (Throwable $e) {
            Log::warning('NHTSA recall lookup failed', compact('make', 'model', 'year') + ['error' => $e->getMessage()]);

            return null;
        }

        return collect($results ?? [])
            ->filter(fn ($row) => filled($row['NHTSACampaignNumber'] ?? null))
            ->map(fn (array $row) => [
                'campaign_number' => $row['NHTSACampaignNumber'],
                'component' => Str::limit((string) ($row['Component'] ?? 'Unspecified'), 190),
                'summary' => (string) ($row['Summary'] ?? ''),
                'consequence' => $row['Consequence'] ?? null,
                'remedy' => $row['Remedy'] ?? null,
                'reported_on' => $this->parseDate($row['ReportReceivedDate'] ?? null),
            ])
            ->unique('campaign_number')
            ->values()
            ->all();
    }

    private function titleCase(string $value): string
    {
        return collect(explode(' ', Str::title(strtolower($value))))
            ->map(fn (string $word) => in_array(strtoupper($word), self::ACRONYMS, true) ? strtoupper($word) : $word)
            ->implode(' ');
    }

    private function parseDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, substr($value, 0, 10))->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
