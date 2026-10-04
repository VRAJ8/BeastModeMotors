<?php

use App\Services\TradeInEstimator;
use App\Support\Finance;

it('depreciates with age, mileage and condition', function () {
    $estimator = new TradeInEstimator;
    $year = (int) date('Y');

    $newish = $estimator->estimate(100000, $year - 1, 5000, 'excellent');
    $older = $estimator->estimate(100000, $year - 5, 5000, 'excellent');
    $highMiles = $estimator->estimate(100000, $year - 1, 80000, 'excellent');
    $poor = $estimator->estimate(100000, $year - 1, 5000, 'poor');

    expect($newish['low'])->toBeLessThan($newish['high'])
        ->and($older['high'])->toBeLessThan($newish['high'])
        ->and($highMiles['high'])->toBeLessThan($newish['high'])
        ->and($poor['high'])->toBeLessThan($newish['high'])
        ->and($newish['high'])->toBeLessThanOrEqual(100000);
});

it('never values a car below a fifth of its price', function () {
    $estimate = (new TradeInEstimator)->estimate(100000, 1980, 0, 'excellent');

    expect($estimate['low'])->toBeGreaterThanOrEqual(18000);
});

it('calculates an amortised monthly payment', function () {
    // $100k, 20% down, 6% APR over 60 months => $80k financed ≈ $1,547/mo.
    expect(Finance::monthlyPayment(100000, 6.0, 60, 20))->toBe(1547)
        ->and(Finance::monthlyPayment(60000, 0.0, 60, 0))->toBe(1000);
});
