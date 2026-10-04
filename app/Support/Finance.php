<?php

namespace App\Support;

class Finance
{
    /**
     * Amortised monthly payment using the showroom's default finance terms.
     */
    public static function monthlyPayment(int $price, ?float $apr = null, ?int $months = null, ?float $depositPercent = null): int
    {
        $apr ??= (float) config('dealership.finance.apr');
        $months ??= (int) config('dealership.finance.term_months');
        $depositPercent ??= (float) config('dealership.finance.deposit_percent');

        $principal = $price * (1 - $depositPercent / 100);
        $rate = $apr / 100 / 12;

        if ($rate == 0.0) {
            return (int) round($principal / $months);
        }

        return (int) round($principal * $rate / (1 - (1 + $rate) ** -$months));
    }
}
