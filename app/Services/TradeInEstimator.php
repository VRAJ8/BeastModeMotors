<?php

namespace App\Services;

/**
 * A deliberately simple, indicative valuation model for trade-ins:
 * a base value depreciated by age and mileage, adjusted for condition.
 * Staff always follow up with a real appraisal.
 */
class TradeInEstimator
{
    public const CONDITION_FACTORS = [
        'excellent' => 1.0,
        'good' => 0.92,
        'fair' => 0.8,
        'poor' => 0.62,
    ];

    /**
     * @return array{low: int, high: int}
     */
    public function estimate(int $originalPrice, int $year, int $mileage, string $condition): array
    {
        $age = max(0, (int) date('Y') - $year);

        // ~15% in the first year, ~10% a year after that, floored at 20% of the original value.
        $ageFactor = $age === 0 ? 1.0 : 0.85 * (0.9 ** ($age - 1));
        $ageFactor = max($ageFactor, 0.2);

        // Every 10k miles above an expected 8k/year knocks ~2% off, capped at 40%.
        $excessMiles = max(0, $mileage - ($age * 8000));
        $mileageFactor = 1 - min(0.4, ($excessMiles / 10000) * 0.02);

        $value = $originalPrice * $ageFactor * $mileageFactor * (self::CONDITION_FACTORS[$condition] ?? 0.8);

        return [
            'low' => (int) (round($value * 0.93 / 500) * 500),
            'high' => (int) (round($value * 1.05 / 500) * 500),
        ];
    }
}
